<?php
// IMPORTANTE: la sesión/conexión deben arrancar ANTES de cualquier salida HTML.
// Venta.php -> Conexion.php hace session_start(); si el HTML ya se imprimió, falla
// ("headers already sent") y se pierde $_SESSION['tenant_db'] → DB equivocada → ticket vacío.

// El ticket se abre normalmente SIN sesión (link compartido / mobile). En ese caso hay que
// resolver el tenant por subdominio (HTTP_X_TENANT de Nginx) o ?t=, igual que index.php/finaliza.php,
// y setearlo en la sesión ANTES de incluir Venta.php (Conexion.php abre $conexion al incluirse,
// leyendo $_SESSION['tenant_db'] → sin esto cae a DB_NAME=atiende y la venta "no se encuentra").
// Solo actúa si no hay sesión: NO pisa la del admin ya logueado.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['tenant_db'])) {
    $ticketSlug = preg_replace('/[^a-z0-9_]/', '', strtolower(($_SERVER['HTTP_X_TENANT'] ?? '') ?: ($_GET['t'] ?? '')));
    if ($ticketSlug !== '') { $_SESSION['tenant_db'] = 'atiende_' . $ticketSlug; }
}

require_once "../modelos/Venta.php";
include_once("../config/Connection.php");
// Alinear el override de Connection (lo usa el bloque "Observación" más abajo) con la DB del
// tenant; si no, la conexión estática iría a DB_NAME y las líneas .001 saldrían de la DB equivocada.
if (!empty($_SESSION['tenant_db'])) { Connection::setDatabase($_SESSION['tenant_db']); }

$venta = new Venta();
$id    = isset($_GET["id"]) ? $_GET["id"] : '';

$rspta = $venta->ventacabecera($id);
$reg   = $rspta ? $rspta->fetch_object() : null;

// Datos de la empresa: fuente de verdad en pedidos_platform.tenants (configurables en Configuración → Empresa
// del tenant o en el superadmin de la plataforma). El logo es un archivo local → vive en bot_config.
$empresa = DB_NAME; $razon = ''; $documento = ''; $telefono = ''; $logo = '';
$slug = (strncmp($_SESSION['tenant_db'] ?? '', 'atiende_', 8) === 0)
    ? substr($_SESSION['tenant_db'], 8) : ($_SESSION['tenant_db'] ?? '');
try {
    $ppT = new PDO('mysql:host=' . DB_HOST . ';dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stT = $ppT->prepare('SELECT nombre, razon_social, cuit, telefono FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
    $stT->execute([$slug]);
    $tEmp = $stT->fetch();
    if ($tEmp) {
        $empresa   = !empty($tEmp['nombre']) ? $tEmp['nombre'] : DB_NAME;
        $razon     = $tEmp['razon_social'] ?? '';
        $documento = $tEmp['cuit'] ?? '';
        $telefono  = $tEmp['telefono'] ?? '';
    }
} catch (Exception $e) { /* sin datos → usa DB_NAME */ }
if (isset($conexion)) {
    $rLogo = mysqli_query($conexion, "SELECT logo FROM bot_config LIMIT 1");
    if ($rLogo && ($rr = mysqli_fetch_assoc($rLogo))) { $logo = $rr['logo'] ?? ''; }
}

if (!function_exists('_money')) {
    function _money($n){ return '$ ' . number_format(floatval($n), 2, ',', '.'); }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Ticket <?php echo htmlspecialchars($id); ?></title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { background:#eef1f6; font-family: 'Inter', 'Segoe UI', Arial, sans-serif; margin:0; padding:28px 12px; color:#212529; }
        .ticket { width:340px; max-width:100%; margin:0 auto; background:#fff; padding:22px 20px; border-radius:10px; box-shadow:0 6px 24px rgba(0,0,0,.12); }
        .ticket .empresa { text-align:center; padding-bottom:10px; }
        .ticket .empresa .nombre { font-weight:800; font-size:1.05rem; letter-spacing:.5px; text-transform:uppercase; color:#6650EA; }
        .ticket .empresa small { display:block; font-size:.78rem; color:#6c757d; line-height:1.5; }
        .ticket hr { border:none; border-top:1px dashed #c7ccd6; margin:12px 0; }
        .ticket .datos { font-size:.82rem; line-height:1.7; }
        .ticket .datos .lbl { color:#6c757d; }
        .ticket .datos b { color:#212529; font-weight:600; }
        .ticket table.items { width:100%; border-collapse:collapse; font-size:.78rem; margin-top:2px; }
        .ticket table.items thead th { text-align:left; border-bottom:1.5px solid #343a40; padding:5px 3px; font-size:.7rem; text-transform:uppercase; letter-spacing:.3px; color:#495057; }
        .ticket table.items tbody td { padding:4px 3px; vertical-align:top; border-bottom:1px solid #f0f1f4; }
        .ticket table.items .amt { text-align:right; white-space:nowrap; }
        .ticket table.items .obs td { color:#6c757d; font-style:italic; border-bottom:1px dashed #e9ecef; }
        .ticket .total { display:flex; justify-content:space-between; align-items:center; font-weight:800; font-size:1rem; border-top:2px solid #343a40; padding-top:8px; margin-top:6px; }
        .ticket .total .val { color:#0a8f57; }
        .ticket .meta { font-size:.78rem; color:#6c757d; margin-top:4px; }
        .ticket .obsbox { font-size:.8rem; margin-top:12px; }
        .ticket .obsbox .t { font-weight:700; text-transform:uppercase; font-size:.7rem; letter-spacing:.3px; color:#495057; }
        .ticket .footer { text-align:center; font-size:.82rem; margin-top:16px; color:#495057; }
        .ticket .footer .gracias { font-weight:700; }
        .ticket .vacio { text-align:center; color:#fa5c7c; font-weight:600; padding:24px 0; }
        .acciones { text-align:center; margin-top:16px; }
        .acciones button { border:none; background:#6650EA; color:#fff; font-weight:600; font-size:.85rem; padding:8px 18px; border-radius:30px; cursor:pointer; box-shadow:0 2px 8px rgba(102,80,234,.35); }
        @media print {
            body { background:#fff; padding:0; }
            .ticket { box-shadow:none; width:100%; border-radius:0; }
            .acciones { display:none; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="empresa">
            <?php if ($logo && file_exists(__DIR__.'/../files/empresa/'.$logo)): ?>
                <img src="../files/empresa/<?php echo htmlspecialchars($logo); ?>" alt="logo" style="max-width:140px;max-height:64px;object-fit:contain;margin-bottom:8px;">
            <?php endif; ?>
            <div class="nombre"><?php echo htmlspecialchars($empresa); ?></div>
            <?php if ($razon): ?><small><?php echo htmlspecialchars($razon); ?></small><?php endif; ?>
            <?php if ($documento): ?><small>CUIT: <?php echo htmlspecialchars($documento); ?></small><?php endif; ?>
            <?php if ($telefono): ?><small>Tel: <?php echo htmlspecialchars($telefono); ?></small><?php endif; ?>
        </div>
        <hr>

        <?php if (!$reg): ?>
            <div class="vacio">No se encontró la venta N° <?php echo htmlspecialchars($id); ?></div>
        <?php else: ?>

        <div class="datos">
            <div><span class="lbl">Fecha:</span> <b><?php echo htmlspecialchars($reg->fecha); ?></b></div>
            <div><span class="lbl">Código:</span> <b><?php echo htmlspecialchars($reg->codigo); ?></b></div>
            <div><span class="lbl">Cliente:</span> <b><?php echo htmlspecialchars($reg->cliente); ?></b></div>
            <div><span class="lbl">Dirección:</span> <b><?php echo htmlspecialchars($reg->direccion); ?></b></div>
            <div><span class="lbl">Localidad:</span> <b><?php echo htmlspecialchars($reg->localidad); ?></b></div>
            <div><span class="lbl">N° de venta:</span> <b><?php echo htmlspecialchars($reg->pedidoid); ?></b></div>
        </div>
        <hr>

        <table class="items">
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th>Cód.</th>
                    <th>Descripción</th>
                    <th class="amt">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rsptad   = $venta->ventadetalles($id);
                $cantidad = 0; $totales = 0.00;
                if ($rsptad) {
                    while ($regd = $rsptad->fetch_object()) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($regd->cantidad).'</td>';
                        echo '<td>'.htmlspecialchars($regd->codigo).'</td>';
                        echo '<td>'.htmlspecialchars(ucwords(strtolower($regd->articulo))).'</td>';
                        echo '<td class="amt">'._money($regd->subtotal).'</td>';
                        echo '</tr>';
                        if (strlen($regd->dato9) > 0) {
                            echo '<tr class="obs"><td>Obs</td><td colspan="3">'.htmlspecialchars($regd->dato9).'</td></tr>';
                        }
                        $totales  += floatval($regd->subtotal);
                        $cantidad += $regd->cantidad;
                    }
                }
                ?>
            </tbody>
        </table>

        <div class="total"><span>TOTAL</span> <span class="val"><?php echo _money($totales); ?></span></div>
        <div class="meta">N° de artículos: <?php echo $cantidad; ?></div>

        <div class="obsbox">
            <div class="t">Observación</div>
            <div>
                <?php
                $result = Connection::runQuery("SELECT * FROM `pedidos` WHERE `pedidoid` like '".$id."' and `producto` like '.001'");
                if ($result) {
                    while ($row = mysqli_fetch_array($result)) {
                        echo htmlspecialchars($row["descripcion"]).'<br>';
                    }
                }
                ?>
            </div>
        </div>

        <div class="footer">
            <div class="gracias">¡Gracias por su compra!</div>
            <div>Utilizamos Atiende</div>
        </div>

        <?php endif; ?>
    </div>

    <div class="acciones">
        <button type="button" onclick="window.print()">🖨 Imprimir</button>
    </div>
</body>
</html>
