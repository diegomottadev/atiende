<?php
use PHPUnit\Framework\TestCase;
use App\ProvisioningService;

class ProvisioningServiceTest extends TestCase
{
    public function test_generateSlug_from_company_name(): void
    {
        $svc = new ProvisioningService();
        $ref = new ReflectionMethod(ProvisioningService::class, 'generateSlug');
        $ref->setAccessible(true);

        $this->assertEquals('mi_empresa', $ref->invoke($svc, 'Mi Empresa'));
        $this->assertEquals('acme_s_a', $ref->invoke($svc, 'ACME S.A.'));
        $this->assertEquals('cafe_bar', $ref->invoke($svc, 'Café & Bar'));
        $this->assertEquals('empresa_123', $ref->invoke($svc, 'Empresa 123'));
    }

    public function test_generateSlug_strips_leading_numbers(): void
    {
        $svc = new ProvisioningService();
        $ref = new ReflectionMethod(ProvisioningService::class, 'generateSlug');
        $ref->setAccessible(true);
        $slug = $ref->invoke($svc, '123 Corp');
        $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]{1,49}$/', $slug);
    }

    /**
     * Regresión: un slug nuevo no debe reutilizar el slug de un tenant ya
     * registrado. Ocupamos el slug base con una fila en `tenants` y exigimos
     * que ensureUniqueSlug (vía devSlug) devuelva el siguiente libre.
     */
    public function test_ensureUniqueSlug_avoids_collision_with_existing_tenant(): void
    {
        $pdo  = $this->platformPdoOrSkip();
        $prov = $this->provisionerPdoOrSkip();
        $svc  = new ProvisioningService();

        $company = 'Ztest Tenant Collision Pp';
        $base    = $this->rawSlug($svc, $company); // 'ztest_tenant_collision_pp'

        $this->cleanup($pdo, $prov, $base);
        try {
            // Libre → devuelve el base
            $this->assertSame($base, $svc->devSlug($company));

            // Ocupamos el base con un tenant real
            $planId = $this->anyPlanId($pdo);
            $pdo->prepare(
                'INSERT INTO tenants (slug, nombre, email, plan_id) VALUES (?,?,?,?)'
            )->execute([$base, $company, 'ztest@example.com', $planId]);

            // Ahora debe saltar al siguiente
            $this->assertSame($base . '_1', $svc->devSlug($company));
        } finally {
            $this->cleanup($pdo, $prov, $base);
        }
    }

    /**
     * Regresión del bug de fuga de datos: una base `atiende_{slug}` que existe
     * FÍSICAMENTE pero NO tiene fila en `tenants` (cliente legacy o base
     * preexistente) debe forzar igualmente un slug nuevo. Antes del fix
     * ensureUniqueSlug solo miraba `tenants`, así que el tenant nuevo
     * terminaba apuntando a la base de otro cliente.
     */
    public function test_ensureUniqueSlug_avoids_collision_with_existing_database(): void
    {
        $pdo  = $this->platformPdoOrSkip();
        $prov = $this->provisionerPdoOrSkip();
        $svc  = new ProvisioningService();

        $company = 'Ztest Db Collision Pp';
        $base    = $this->rawSlug($svc, $company); // 'ztest_db_collision_pp'

        $this->cleanup($pdo, $prov, $base);
        try {
            // Libre → devuelve el base
            $this->assertSame($base, $svc->devSlug($company));

            // Creamos SOLO la base física, sin fila en tenants
            $prov->exec("CREATE DATABASE `atiende_{$base}` DEFAULT CHARACTER SET utf8mb4");

            // Debe saltar al siguiente aunque no haya tenant registrado
            $this->assertSame(
                $base . '_1',
                $svc->devSlug($company),
                'Una base atiende_* preexistente sin tenant debe evitar la colisión'
            );
        } finally {
            $this->cleanup($pdo, $prov, $base);
        }
    }

    /**
     * Regresión 6(b): el split de SQL no debe partir en un ';' que está
     * dentro de un string literal ni dentro de un comentario.
     */
    public function test_splitSqlStatements_respects_quotes_and_comments(): void
    {
        $sql = <<<SQL
        CREATE TABLE a (
          id INT,
          nota VARCHAR(50) DEFAULT 'hola; chau'
        );
        -- comentario con ; adentro
        INSERT INTO a (id, nota) VALUES (1, 'x; y');
        /* bloque ; con ; varios ; */
        CREATE TABLE b (id INT);
        SQL;

        $stmts = ProvisioningService::splitSqlStatements($sql);

        $this->assertCount(3, $stmts, 'Debe haber exactamente 3 sentencias');
        $this->assertStringContainsString('CREATE TABLE a', $stmts[0]);
        $this->assertStringContainsString("'hola; chau'", $stmts[0]);
        $this->assertStringContainsString("'x; y'", $stmts[1]);
        $this->assertStringContainsString('CREATE TABLE b', $stmts[2]);
    }

    public function test_splitSqlStatements_ignores_trailing_whitespace_and_empty(): void
    {
        $this->assertSame([], ProvisioningService::splitSqlStatements("  ;  ; \n -- nada\n"));
        $this->assertSame(['SELECT 1'], ProvisioningService::splitSqlStatements('SELECT 1;'));
    }

    /**
     * Regresión 7: teardown en dry-run valida precondiciones, devuelve el
     * plan y NO destruye nada (el tenant sigue activo y sin deleted_at).
     */
    public function test_teardown_dry_run_does_not_destroy(): void
    {
        $pdo = $this->platformPdoOrSkip();
        $svc = new ProvisioningService();
        $slug = 'ztest_teardown_dry';

        $this->cleanupTenantAndSubs($pdo, $slug);
        try {
            $planId = $this->anyPlanId($pdo);
            $pdo->prepare(
                'INSERT INTO tenants (slug, nombre, email, estado, db_name, plan_id) VALUES (?,?,?,?,?,?)'
            )->execute([$slug, 'Ztest Teardown', 'ztest@example.com', 'activo', 'atiende_' . $slug, $planId]);
            $tenantId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO subscriptions (tenant_id, provider, provider_subscription_id, estado, periodo_fin)
                 VALUES (?,?,?,?,NOW())'
            )->execute([$tenantId, 'stripe', 'ztest_sub_' . $tenantId, 'cancelada']);

            $plan = $svc->teardown($tenantId, true);

            $this->assertTrue($plan['dry_run']);
            $this->assertNotEmpty($plan['acciones']);
            $this->assertSame($slug, $plan['slug']);

            // No destruyó nada
            $row = $pdo->query("SELECT estado, deleted_at FROM tenants WHERE id = $tenantId")->fetch();
            $this->assertSame('activo', $row['estado']);
            $this->assertNull($row['deleted_at']);
        } finally {
            $this->cleanupTenantAndSubs($pdo, $slug);
        }
    }

    public function test_teardown_blocks_when_subscription_not_cancelled(): void
    {
        $pdo = $this->platformPdoOrSkip();
        $svc = new ProvisioningService();
        $slug = 'ztest_teardown_block';

        $this->cleanupTenantAndSubs($pdo, $slug);
        try {
            $planId = $this->anyPlanId($pdo);
            $pdo->prepare(
                'INSERT INTO tenants (slug, nombre, email, estado, db_name, plan_id) VALUES (?,?,?,?,?,?)'
            )->execute([$slug, 'Ztest Block', 'ztest@example.com', 'activo', 'atiende_' . $slug, $planId]);
            $tenantId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO subscriptions (tenant_id, provider, provider_subscription_id, estado, periodo_fin)
                 VALUES (?,?,?,?,NOW())'
            )->execute([$tenantId, 'stripe', 'ztest_sub_' . $tenantId, 'activa']);

            $this->expectException(\RuntimeException::class);
            $svc->teardown($tenantId, true);
        } finally {
            $this->cleanupTenantAndSubs($pdo, $slug);
        }
    }

    // --- helpers ---

    private function cleanupTenantAndSubs(\PDO $pdo, string $slug): void
    {
        $pdo->prepare(
            'DELETE s FROM subscriptions s JOIN tenants t ON t.id = s.tenant_id WHERE t.slug = ?'
        )->execute([$slug]);
        $pdo->prepare(
            'DELETE pu FROM platform_users pu JOIN tenants t ON t.id = pu.tenant_id WHERE t.slug = ?'
        )->execute([$slug]);
        $pdo->prepare('DELETE FROM tenants WHERE slug = ?')->execute([$slug]);
    }

    private function rawSlug(ProvisioningService $svc, string $company): string
    {
        $ref = new ReflectionMethod(ProvisioningService::class, 'generateSlug');
        $ref->setAccessible(true);
        return $ref->invoke($svc, $company);
    }

    private function platformPdoOrSkip(): \PDO
    {
        try {
            $pdo = getPlatformPDO();
            $pdo->query('SELECT 1');
            return $pdo;
        } catch (\Throwable $e) {
            $this->markTestSkipped('MariaDB (saas_platform) no disponible: ' . $e->getMessage());
        }
    }

    private function provisionerPdoOrSkip(): \PDO
    {
        try {
            $prov = getProvisionerPDO();
            $prov->query('SELECT 1');
            return $prov;
        } catch (\Throwable $e) {
            $this->markTestSkipped('Usuario provisioner no disponible: ' . $e->getMessage());
        }
    }

    private function anyPlanId(\PDO $pdo): int
    {
        $id = $pdo->query('SELECT id FROM plans ORDER BY id LIMIT 1')->fetchColumn();
        if ($id === false) {
            $this->markTestSkipped('No hay planes en saas_platform para el FK de tenants');
        }
        return (int) $id;
    }

    /**
     * Borra filas de tenants y bases atiende_* para $base y $base_1.
     * Guardado por prefijo 'ztest_' para que un DROP nunca pueda tocar
     * una base real por error.
     */
    private function cleanup(\PDO $pdo, \PDO $prov, string $base): void
    {
        foreach ([$base, $base . '_1'] as $slug) {
            $pdo->prepare('DELETE FROM tenants WHERE slug = ?')->execute([$slug]);
            if (strpos($slug, 'ztest_') === 0) {
                $prov->exec("DROP DATABASE IF EXISTS `atiende_{$slug}`");
            }
        }
    }
}
