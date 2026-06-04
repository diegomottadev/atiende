<?php
namespace App;

class ProvisioningService
{
    public function provision(int $checkoutSessionId): void
    {
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare('SELECT * FROM checkout_sessions WHERE id = ? AND estado = ?');
        $stmt->execute([$checkoutSessionId, 'pendiente']);
        $session = $stmt->fetch();
        if (!$session) {
            throw new \RuntimeException("checkout_session $checkoutSessionId not found or not pending");
        }

        $slug     = $this->ensureUniqueSlug($this->generateSlug($session['empresa']));
        $dbName   = 'atiende_' . $slug;
        $tempPass = bin2hex(random_bytes(8));

        $paso = 0;
        $userId = null;
        $tenantId = null;
        try {
            $prov = getProvisionerPDO();
            // Sin IF NOT EXISTS a propósito: si atiende_{slug} ya existe (colisión
            // o race con otro alta simultáneo) el CREATE falla y abortamos.
            // paso=1 recién DESPUÉS del CREATE exitoso → si el CREATE falla,
            // paso queda en 0 y la compensación NO ejecuta el DROP, así nunca
            // borramos una base preexistente que es de otro cliente.
            $prov->exec("CREATE DATABASE `$dbName` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");
            $paso = 1;

            $paso = 2;
            $sql = file_get_contents(ROOT . '/templates/sql/tenant_schema.sql');
            $prov->exec("USE `$dbName`");
            // No usar explode(';'): rompería el alta ante un ';' dentro de un
            // string literal, un COMMENT o un comentario del dump. El splitter
            // respeta comillas, backticks y comentarios.
            foreach (self::splitSqlStatements($sql) as $statement) {
                $prov->exec($statement);
            }

            $paso = 3;
            $menuJson = file_get_contents(ROOT . '/templates/bot/default_menu.json');
            // $prov sigue posicionado en `USE atiende_<slug>` (paso 2). Sembramos el
            // menú default en la bot_config del tenant (antes iba a axbot.empresa).
            $prov->prepare('INSERT INTO bot_config (id, menu_json) VALUES (1, ?)')
                 ->execute([$menuJson]);

            $paso = 4;
            $hash = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                'INSERT INTO platform_users (tenant_id, email, password_hash, rol) VALUES (?,?,?,?)'
            )->execute([null, $session['email'], $hash, 'tenant_admin']);
            $userId = (int)$pdo->lastInsertId();

            $paso = 5;
            $pdo->prepare(
                'INSERT INTO tenants (slug, nombre, email, estado, db_name, plan_id) VALUES (?,?,?,?,?,?)'
            )->execute([$slug, $session['empresa'], $session['email'], 'pendiente_whatsapp', $dbName, $session['plan_id']]);
            $tenantId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE platform_users SET tenant_id = ? WHERE id = ?')->execute([$tenantId, $userId]);

            $paso = 6;
            $pdo->prepare(
                'INSERT INTO subscriptions (tenant_id, provider, provider_subscription_id, estado, periodo_fin) VALUES (?,?,?,?,DATE_ADD(NOW(), INTERVAL 1 MONTH))'
            )->execute([$tenantId, $session['provider'], $session['provider_ref'] ?? 'pending_' . $session['session_token'], 'activa']);

            $paso = 7;
            MailService::send($session['email'], 'Bienvenido a Pedidos Platform', 'bienvenida', [
                'empresa'  => $session['empresa'],
                'email'    => $session['email'],
                'password' => $tempPass,
            ]);
            MailService::send($_ENV['SUPERADMIN_EMAIL'], 'Nuevo tenant: ' . $session['empresa'], 'pendiente_whatsapp', [
                'empresa'   => $session['empresa'],
                'tenant_id' => $tenantId,
            ]);

            $paso = 8;
            $pdo->prepare('UPDATE checkout_sessions SET estado = ? WHERE id = ?')->execute(['completado', $checkoutSessionId]);

        } catch (\Throwable $e) {
            error_log('[ProvisioningService] FAIL paso=' . $paso . ' ' . $e->getMessage());
            $this->compensate($paso, $dbName, $slug, $userId, $tenantId, $checkoutSessionId, $pdo, $e->getMessage());
            throw $e;
        }

        // En dev: notificar al script de Windows para agregar el subdominio al hosts
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            $pendingFile = ROOT . '/pending_hosts.txt';
            file_put_contents($pendingFile, $slug . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    private function compensate(int $paso, string $dbName, string $slug, ?int $userId, ?int $tenantId, int $sessionId, \PDO $pdo, string $errorMessage): void
    {
        $compensated     = 0;
        $compensateError = null;
        try {
            if ($paso >= 6 && $tenantId) {
                $pdo->prepare('DELETE FROM subscriptions WHERE tenant_id = ?')->execute([$tenantId]);
            }
            if ($paso >= 5 && $tenantId) {
                $pdo->prepare('DELETE FROM tenants WHERE id = ?')->execute([$tenantId]);
            }
            if ($paso >= 4 && $userId) {
                $pdo->prepare('DELETE FROM platform_users WHERE id = ?')->execute([$userId]);
            }
            if ($paso >= 1) {
                getProvisionerPDO()->exec("DROP DATABASE IF EXISTS `$dbName`");
            }
            $pdo->prepare('UPDATE checkout_sessions SET estado = ? WHERE id = ?')->execute(['fallido', $sessionId]);
            $compensated = 1;
        } catch (\Throwable $ce) {
            error_log('[ProvisioningService] compensate FAIL: ' . $ce->getMessage());
            $compensateError = $ce->getMessage();
        }

        // Registro durable del fallo. Si la compensación quedó incompleta
        // (compensated=0) hay una DB/registro huérfano que reconciliar con
        // cli/reconcile_provisioning.php. Antes esto se perdía en error_log.
        try {
            $pdo->prepare(
                'INSERT INTO provisioning_failures
                   (checkout_session_id, slug, db_name, paso, error, compensated, compensate_error)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([$sessionId, $slug, $dbName, $paso, $errorMessage, $compensated, $compensateError]);
        } catch (\Throwable $le) {
            error_log('[ProvisioningService] no se pudo registrar provisioning_failure: ' . $le->getMessage());
        }
    }

    public function suspend(int $tenantId): void
    {
        $pdo = getPlatformPDO();
        $pdo->prepare("UPDATE tenants SET estado = 'suspendido' WHERE id = ?")->execute([$tenantId]);
    }

    public function reactivate(int $tenantId): void
    {
        $pdo = getPlatformPDO();
        $pdo->prepare("UPDATE tenants SET estado = 'activo' WHERE id = ?")->execute([$tenantId]);
    }

    /**
     * Baja total del tenant. Con $dryRun=true valida las precondiciones y
     * devuelve el plan de acciones SIN ejecutar nada destructivo — pensado
     * para previsualizar en el superadmin antes de confirmar un DROP DATABASE.
     * Devuelve un array con la descripción de lo que hizo (o haría).
     */
    public function teardown(int $tenantId, bool $dryRun = false): array
    {
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare(
            "SELECT t.slug, t.db_name, s.estado AS sub_estado FROM tenants t
             LEFT JOIN subscriptions s ON s.tenant_id = t.id
             WHERE t.id = ? AND t.deleted_at IS NULL"
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException("Tenant $tenantId not found");
        }
        if ($row['sub_estado'] !== 'cancelada') {
            throw new \RuntimeException("Cannot teardown: subscription must be cancelled first");
        }

        $plan = [
            "DROP DATABASE IF EXISTS `{$row['db_name']}`  (IRREVERSIBLE: borra todos los datos del tenant)",
            "UPDATE tenants SET estado='cancelado', deleted_at=NOW() WHERE id={$tenantId}",
            "UPDATE platform_users SET deleted_at=NOW() WHERE tenant_id={$tenantId}",
            "UPDATE subscriptions SET deleted_at=NOW() WHERE tenant_id={$tenantId}",
        ];

        $resultado = [
            'dry_run'  => $dryRun,
            'tenant_id'=> $tenantId,
            'slug'     => $row['slug'],
            'db_name'  => $row['db_name'],
            'acciones' => $plan,
        ];

        if ($dryRun) {
            return $resultado;
        }

        getProvisionerPDO()->exec("DROP DATABASE IF EXISTS `{$row['db_name']}`");
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE tenants SET estado='cancelado', deleted_at=? WHERE id=?")->execute([$now, $tenantId]);
        $pdo->prepare("UPDATE platform_users SET deleted_at=? WHERE tenant_id=?")->execute([$now, $tenantId]);
        $pdo->prepare("UPDATE subscriptions SET deleted_at=? WHERE tenant_id=?")->execute([$now, $tenantId]);

        return $resultado;
    }

    public function updatePeriodFin(int $tenantId, \DateTime $newPeriodFin): void
    {
        getPlatformPDO()->prepare(
            "UPDATE subscriptions SET periodo_fin=?, payment_failure_count=0, grace_period_fin=NULL WHERE tenant_id=? AND estado='activa'"
        )->execute([$newPeriodFin->format('Y-m-d H:i:s'), $tenantId]);
    }

    public function devSlug(string $companyName): string
    {
        return $this->ensureUniqueSlug($this->generateSlug($companyName));
    }

    /**
     * Divide un script SQL en sentencias por `;`, respetando comillas simples
     * y dobles, identificadores con backtick, comentarios de línea (-- y #) y
     * comentarios de bloque estilo C. Así un `;` dentro de un string o un
     * comentario no parte la sentencia.
     *
     * Limitación conocida: no interpreta `DELIMITER` (mysqldump lo usa para
     * triggers/procedures). El tenant_schema.sql es DDL puro (CREATE TABLE),
     * así que no aplica; si en el futuro se agregan rutinas, hay que importar
     * con un cliente que soporte DELIMITER.
     */
    public static function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $len        = strlen($sql);
        $inSingle = $inDouble = $inBacktick = false;
        $inLineComment = $inBlockComment = false;
        // ¿el fragmento actual tiene SQL real (no solo comentarios/espacios)?
        // Sin esto, un fragmento que es puro comentario llegaría a
        // PDO::exec() y MySQL devuelve error 1065 "Query was empty".
        $hasContent = false;

        $flush = function () use (&$statements, &$current, &$hasContent) {
            $trimmed = trim($current);
            if ($trimmed !== '' && $hasContent) {
                $statements[] = $trimmed;
            }
            $current    = '';
            $hasContent = false;
        };

        for ($i = 0; $i < $len; $i++) {
            $ch   = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($inLineComment) {
                $current .= $ch;
                if ($ch === "\n") { $inLineComment = false; }
                continue;
            }
            if ($inBlockComment) {
                $current .= $ch;
                if ($ch === '*' && $next === '/') { $current .= $next; $i++; $inBlockComment = false; }
                continue;
            }
            if ($inSingle || $inDouble || $inBacktick) {
                $current .= $ch;
                $hasContent = true;
                // Escape con backslash dentro de comillas (no aplica a backtick)
                if ($ch === '\\' && ($inSingle || $inDouble)) {
                    if ($next !== '') { $current .= $next; $i++; }
                    continue;
                }
                if ($inSingle && $ch === "'")      { $inSingle = false; }
                elseif ($inDouble && $ch === '"')  { $inDouble = false; }
                elseif ($inBacktick && $ch === '`') { $inBacktick = false; }
                continue;
            }

            // Fuera de strings/comentarios: detectar aperturas
            if ($ch === '-' && $next === '-') { $inLineComment = true; $current .= $ch; continue; }
            if ($ch === '#')                  { $inLineComment = true; $current .= $ch; continue; }
            if ($ch === '/' && $next === '*') { $inBlockComment = true; $current .= $ch . $next; $i++; continue; }
            if ($ch === "'")                  { $inSingle = true;  $hasContent = true; $current .= $ch; continue; }
            if ($ch === '"')                  { $inDouble = true;  $hasContent = true; $current .= $ch; continue; }
            if ($ch === '`')                  { $inBacktick = true; $hasContent = true; $current .= $ch; continue; }

            if ($ch === ';') { $flush(); continue; }

            if (!ctype_space($ch)) { $hasContent = true; }
            $current .= $ch;
        }
        $flush();
        return $statements;
    }

    private function generateSlug(string $companyName): string
    {
        $slug = mb_strtolower($companyName, 'UTF-8');

        // Transliteración determinista de diacríticos. NO usar
        // iconv('ASCII//TRANSLIT'): su salida depende de la libc del host
        // (glibc vs musl/Alpine), así que 'Café' daría 'cafe' en una y
        // 'caf_e' en otra → nombres de DB distintos según dónde corra.
        $map = [
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','ã'=>'a','å'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','õ'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
            'ñ'=>'n','ç'=>'c',
        ];
        $slug = strtr($slug, $map);

        // Cualquier resto no [a-z0-9] (incluido multibyte sin mapear) → '_'
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        if (strlen($slug) === 0 || !ctype_alpha($slug[0])) {
            $slug = 'empresa_' . $slug;
        }
        return substr($slug, 0, 50);
    }

    private function ensureUniqueSlug(string $base): string
    {
        $pdo  = getPlatformPDO();
        $prov = getProvisionerPDO();
        $slug = $base;
        $i    = 1;
        while (true) {
            // 1) ¿hay un tenant registrado con este slug?
            $stmt = $pdo->prepare('SELECT id FROM tenants WHERE slug = ?');
            $stmt->execute([$slug]);
            $tenantExists = (bool) $stmt->fetch();

            // 2) ¿ya existe físicamente la base atiende_{slug}? Defensa contra fuga
            //    de datos entre clientes: un slug nuevo NUNCA debe reutilizar
            //    una base preexistente. Las bases atiende_* pueden anteceder a esta
            //    plataforma (clientes legacy), así que chequear solo la tabla
            //    `tenants` no alcanza — hay que mirar el catálogo real.
            $dbStmt = $prov->prepare(
                'SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'
            );
            $dbStmt->execute(['atiende_' . $slug]);
            $dbExists = (bool) $dbStmt->fetch();

            if (!$tenantExists && !$dbExists) {
                return $slug;
            }
            $slug = $base . '_' . $i++;
        }
    }
}
