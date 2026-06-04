<?php
/**
 * [DEV] Apunta el número de WhatsApp de prueba a otro tenant.
 *
 * El webhook rutea por `whatsapp_phone_id`, así que para probar el bot con otra
 * empresa basta con mover ese phone_id (+ token + app_secret = mismas credenciales
 * del mismo número físico) al tenant destino, y liberarlo de los demás para que el
 * lookup no sea ambiguo.
 *
 * Uso (dentro del contenedor app):
 *   docker exec -it demo_atiende_app php /var/www/atiende/switch_wa_tenant.php demo
 *   docker exec -it demo_atiende_app php /var/www/atiende/switch_wa_tenant.php corp   // volver
 *
 * BORRAR este archivo cuando termines de probar (es solo para desarrollo).
 */

require __DIR__ . '/config/database.php';

$target = $argv[1] ?? '';
if ($target === '') {
    fwrite(STDERR, "Uso: php switch_wa_tenant.php <slug>\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=pedidos_platform;charset=utf8mb4',
    DB_USERNAME, DB_PASSWORD,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Tenant destino
$t = $pdo->prepare('SELECT id, slug, estado FROM tenants WHERE slug = ? AND deleted_at IS NULL');
$t->execute([$target]);
$dest = $t->fetch();
if (!$dest) { fwrite(STDERR, "Tenant '$target' no existe.\n"); exit(1); }
if ($dest['estado'] !== 'activo') { fwrite(STDERR, "Tenant '$target' no está activo (estado={$dest['estado']}).\n"); exit(1); }

// Tenant que tiene hoy el número (la fuente de las credenciales)
$src = $pdo->query(
    "SELECT slug, whatsapp_phone_id, whatsapp_waba_id, whatsapp_token_enc, whatsapp_app_secret_enc
     FROM tenants
     WHERE whatsapp_phone_id IS NOT NULL AND whatsapp_phone_id <> '' AND deleted_at IS NULL
     ORDER BY id LIMIT 1"
)->fetch();

if (!$src) { fwrite(STDERR, "Ningún tenant tiene un whatsapp_phone_id cargado. Configurá las credenciales primero.\n"); exit(1); }

echo "Número actualmente en: {$src['slug']} (phone_id={$src['whatsapp_phone_id']})\n";

if ($src['slug'] === $target) {
    echo "El número YA apunta a '$target'. Nada que hacer.\n";
    exit(0);
}

$pdo->beginTransaction();
// 1) Liberar el phone_id de cualquier otro tenant (evita ambigüedad en el lookup del webhook)
$pdo->prepare("UPDATE tenants SET whatsapp_phone_id = '' WHERE slug <> ?")->execute([$target]);
// 2) Copiar las credenciales del número al tenant destino
$pdo->prepare(
    'UPDATE tenants
        SET whatsapp_phone_id = ?, whatsapp_waba_id = ?, whatsapp_token_enc = ?, whatsapp_app_secret_enc = ?
      WHERE slug = ?'
)->execute([
    $src['whatsapp_phone_id'], $src['whatsapp_waba_id'],
    $src['whatsapp_token_enc'], $src['whatsapp_app_secret_enc'],
    $target,
]);
$pdo->commit();

echo "OK → el número {$src['whatsapp_phone_id']} ahora rutea al tenant '$target'.\n";
echo "Mandá un WhatsApp al número y vas a hablar con el bot de '$target'.\n";
echo "Para volver:  php switch_wa_tenant.php {$src['slug']}\n";
