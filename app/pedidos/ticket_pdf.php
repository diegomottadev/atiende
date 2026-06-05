<?php
define('__ROOT__', dirname(__DIR__));
require_once __ROOT__ . '/config/global.php';
require_once __ROOT__ . '/config/Connection.php';
require_once __ROOT__ . '/fpdf181/fpdf.php';

function generarTicketPdf($pedidoId)
{
    $pedidoId = intval($pedidoId);

    $req = Connection::runQuery(
        "SELECT p.pedidoid, p.clienteId, c.razonSocial, c.direccion,
                p.producto, p.descripcion, p.cantidad, p.precio, p.subtotal, p.fecha
         FROM pedidos p
         INNER JOIN clientes c ON c.codigo = p.clienteId
         WHERE p.pedidoid = $pedidoId
         ORDER BY p.id"
    );

    $items = [];
    $cabecera = null;
    while ($row = mysqli_fetch_assoc($req)) {
        if (!$cabecera) $cabecera = $row;
        if ($row['producto'] !== '.001') {
            $items[] = $row;
        }
    }

    if (!$cabecera) return null;

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(20, 15, 20);
    $pdf->SetTitle('Pedido ' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT));
    $pdf->SetAuthor('Atiende');
    $pdf->SetCreator('Atiende');
    $pdf->SetSubject('Comprobante de pedido N ' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT));
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);

    $w = 170; // ancho útil (A4 210 - márgenes 20*2)

    // ===== Datos de la empresa (tenant) — viven en bot_config (singleton id=1) =====
    $empRow = mysqli_fetch_assoc(Connection::runQuery(
        "SELECT nombre_empresa, razon_social, cuit, telefono, logo FROM bot_config LIMIT 1"
    ));
    $empNombre = ($empRow && trim($empRow['nombre_empresa'] ?? '') !== '') ? $empRow['nombre_empresa'] : 'Atiende';
    $empRazon  = $empRow['razon_social'] ?? '';
    $empCuit   = $empRow['cuit'] ?? '';
    $empTel    = $empRow['telefono'] ?? '';
    $empLogo   = $empRow['logo'] ?? '';

    // Logo de la empresa (opcional, centrado arriba del encabezado)
    $logoPath = ($empLogo !== '') ? __ROOT__ . '/files/empresa/' . $empLogo : '';
    if ($logoPath !== '' && is_file($logoPath) && @getimagesize($logoPath) !== false) {
        $info  = getimagesize($logoPath);
        $ratio = ($info[1] > 0) ? $info[0] / $info[1] : 1;
        $logoH = 20; // mm
        $logoW = $logoH * $ratio;
        if ($logoW > 60) { $logoW = 60; $logoH = $logoW / $ratio; }
        $pdf->Image($logoPath, (210 - $logoW) / 2, 15, $logoW, $logoH);
        $pdf->Ln($logoH + 2);
    }

    // Encabezado con fondo de color — nombre de la empresa
    $pdf->SetFillColor(60, 141, 188);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell($w, 12, utf8_decode($empNombre), 0, 1, 'C', true);

    // Línea secundaria con razón social / CUIT / teléfono (solo lo que esté cargado)
    $detalle = [];
    if (trim($empRazon) !== '') $detalle[] = $empRazon;
    if (trim($empCuit)  !== '') $detalle[] = 'CUIT: ' . $empCuit;
    if (trim($empTel)   !== '') $detalle[] = 'Tel: ' . $empTel;
    if ($detalle) {
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell($w, 7, utf8_decode(implode('   |   ', $detalle)), 0, 1, 'C', true);
    }
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(6);

    // Número de pedido y fecha
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell($w, 8, 'PEDIDO N ' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $fecha = date('d/m/Y H:i', strtotime($cabecera['fecha']));
    $pdf->Cell($w, 6, $fecha, 0, 1, 'C');
    $pdf->Ln(4);

    // Cliente
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell($w, 7, 'DATOS DEL CLIENTE', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(25, 6, 'Cliente:', 0, 0);
    $pdf->Cell($w - 25, 6, utf8_decode($cabecera['razonSocial']), 0, 1);
    $pdf->Cell(25, 6, 'Direccion:', 0, 0);
    $pdf->Cell($w - 25, 6, utf8_decode($cabecera['direccion']), 0, 1);
    $pdf->Ln(4);

    // Tabla de items — encabezado
    $pdf->SetFillColor(60, 141, 188);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 7, 'Articulo', 0, 0, 'L', true);
    $pdf->Cell(30, 7, 'Cantidad', 0, 0, 'C', true);
    $pdf->Cell(30, 7, 'Precio', 0, 0, 'R', true);
    $pdf->Cell(30, 7, 'Subtotal', 0, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);

    // Items
    $pdf->SetFont('Arial', '', 9);
    $total = 0;
    $fill = false;
    foreach ($items as $item) {
        $pdf->SetFillColor(249, 249, 249);
        $desc = utf8_decode(substr($item['descripcion'], 0, 45));
        $pdf->Cell(80, 6, $desc, 0, 0, 'L', $fill);
        $pdf->Cell(30, 6, $item['cantidad'], 0, 0, 'C', $fill);
        $pdf->Cell(30, 6, '$' . number_format($item['precio'], 2, ',', '.'), 0, 0, 'R', $fill);
        $pdf->Cell(30, 6, '$' . number_format($item['subtotal'], 2, ',', '.'), 0, 1, 'R', $fill);
        $total += floatval($item['subtotal']);
        $fill = !$fill;
    }

    // Total
    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(60, 141, 188);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(140, 8, 'TOTAL', 0, 0, 'R', true);
    $pdf->Cell(30, 8, '$' . number_format($total, 2, ',', '.'), 0, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(6);

    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell($w, 6, 'Gracias por su compra!', 0, 1, 'C');

    $fechaArchivo = date('Ymd_His', strtotime($cabecera['fecha']));
    $clienteNombre = preg_replace('/[^A-Za-z0-9\-]/', '_', $cabecera['razonSocial']);
    $filename = str_pad($pedidoId, 6, '0', STR_PAD_LEFT)
        . '-' . $cabecera['clienteId']
        . '-' . $clienteNombre
        . '-' . $fechaArchivo
        . '.pdf';

    $tmpFile = sys_get_temp_dir() . '/' . $filename;
    $pdf->Output('F', $tmpFile);
    return ['path' => $tmpFile, 'filename' => $filename];
}
