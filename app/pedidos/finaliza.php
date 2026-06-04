<?php
include_once("../config/Connection.php");

// Teléfono de la persona que escribe al chat (el cliente). SIEMPRE su número,
// nunca el del negocio: el botón "Volver a WhatsApp" reabre la conversación con él.
$telefono = '';
$ped = isset($_GET['ped']) ? preg_replace('/\D/', '', $_GET['ped']) : '';
if ($ped !== '') {
    // 1. Número guardado en el pedido confirmado
    $request = Connection::runQuery("SELECT telefono FROM `pedidos` WHERE `pedidoid` = '" . $ped . "' AND telefono <> '' ORDER BY fecha DESC LIMIT 1");
    if ($request !== null && mysqli_num_rows($request) > 0) {
        $telefono = mysqli_fetch_assoc($request)['telefono'];
    }
    // 2. Respaldo: número del link original (pedidos.pedidoid == link_pedidos.id),
    //    que el bot guarda con el WhatsApp del cliente.
    if ($telefono === '') {
        $request = Connection::runQuery("SELECT telefono FROM `link_pedidos` WHERE `id` = '" . $ped . "' AND telefono <> '' LIMIT 1");
        if ($request !== null && mysqli_num_rows($request) > 0) {
            $telefono = mysqli_fetch_assoc($request)['telefono'];
        }
    }
}
$telefono = preg_replace('/\D/', '', $telefono); // wa.me: solo dígitos
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
            <a href="https://wa.me/<?php echo $telefono; ?>" target="_blank" class="btn-whatsapp">
                <i class="uil uil-whatsapp"></i> Volver a WhatsApp
            </a>
        </div>
        <div class="finish-foot">&copy; <?php echo date('Y'); ?> <span>Atiende</span></div>
    </div>
</body>
</html>
