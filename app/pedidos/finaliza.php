<?php
include_once("../config/Connection.php");
include_once("../config/Telefono.php");

// Resolver el tenant igual que index.php: subdominio (HTTP_X_TENANT de Nginx) o ?t=.
// Sin esto, Connection cae a la DB base 'atiende' (sin bot_config) y no hay teléfono.
// Nginx setea HTTP_X_TENANT='' (cadena vacía, no null) para el host base, por eso se usa
// ?: y no ?? : así un acceso legacy por ?t= sigue funcionando cuando no hay subdominio.
$tenantSlug = preg_replace('/[^a-z0-9_]/', '', strtolower(($_SERVER['HTTP_X_TENANT'] ?? '') ?: ($_GET['t'] ?? '')));
if ($tenantSlug !== '') {
    Connection::setDatabase('atiende_' . $tenantSlug);
}

// Teléfono de WhatsApp del NEGOCIO. El botón "Volver a WhatsApp" reabre la
// conversación con el bot/negocio (wa.me/<X> abre el chat CON X), así el cliente
// vuelve al chat donde recibe la confirmación. Sale de bot_config.telefono del
// tenant (vía getWebMasterConfig), no del pedido.
$telefono = '';
$cfg    = function_exists('getWebMasterConfig') ? getWebMasterConfig() : [];
$digits = preg_replace('/\D/', '', (string) ($cfg['data']['telefono'] ?? ''));
if ($digits !== '') {
    // wa.me exige formato internacional (wa_id). Se normaliza según el país del tenant
    // (bot_config.pais, default AR) vía Telefono::normalizar.
    $paisT    = $cfg['data']['pais'] ?? 'AR';
    $telefono = Telefono::normalizar($digits, $paisT);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listo | Pedidos</title>
    <link href="/public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="/public/bs5/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/css/icons.min.css">
    <style>
        :root { --ap: #6c63ff; --am: #3cd4ac; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .finish-card {
            background: #fff;
            width: 100%;
            max-width: 420px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(108, 99, 255, .15);
            overflow: hidden;
            text-align: center;
        }
        .finish-head {
            background: linear-gradient(135deg, var(--ap), #8a83ff);
            padding: 34px 20px 30px;
        }
        .finish-check {
            width: 84px;
            height: 84px;
            margin: 0 auto;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
        }
        .finish-check i { font-size: 3rem; color: var(--am); line-height: 1; }
        .finish-body { padding: 28px 26px 32px; }
        .finish-title { font-size: 1.5rem; font-weight: 800; color: #333; margin: 0 0 8px; }
        .finish-text { color: #777; font-size: .95rem; line-height: 1.5; margin: 0 0 24px; }
        .finish-text strong { color: #444; }
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: var(--am);
            color: #fff;
            border: none;
            border-radius: 26px;
            padding: 13px 20px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: background .15s, transform .1s;
        }
        .btn-whatsapp:hover { background: #2ebf9a; color: #fff; transform: translateY(-1px); }
        .btn-whatsapp i { font-size: 1.3rem; }
        .finish-foot { font-size: .8rem; color: #c2c2c2; padding-bottom: 18px; font-weight: 700; letter-spacing: .5px; }
        .finish-foot span { color: var(--ap); }
    </style>
</head>
<body>
    <div class="finish-card">
        <div class="finish-head">
            <div class="finish-check"><i class="uil uil-check"></i></div>
        </div>
        <div class="finish-body">
            <h1 class="finish-title">¡Listo!</h1>
            <p class="finish-text">Tu pedido se registró correctamente.<br><strong>Verificá tu WhatsApp</strong> para confirmarlo.</p>
<?php if ($telefono !== ''): ?>
            <a href="https://wa.me/<?php echo $telefono; ?>" target="_blank" class="btn-whatsapp">
                <i class="uil uil-whatsapp"></i> Volver a WhatsApp
            </a>
<?php endif; ?>
        </div>
        <div class="finish-foot">&copy; <?php echo date('Y'); ?> <span>Atiende</span></div>
    </div>
</body>
</html>
