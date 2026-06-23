<?php
define('__ROOT__', dirname(__DIR__));
require_once __ROOT__ . '/config/global.php';
require_once __ROOT__ . '/config/Connection.php';
require_once __ROOT__ . '/fpdf181/fpdf.php';

// Subclase con utilidades para el recibo: NbLines (medir cuántas líneas ocupará un
// MultiCell, para calcular el alto del "papel" antes de crearlo) y DashedRow (línea
// horizontal punteada, como los separadores de la vista web /ticket/{id}).
if (!class_exists('TicketPDF')) {
    class TicketPDF extends FPDF
    {
        // Estándar de FPDF: cuántas líneas ocupará un MultiCell de ancho $w con $txt.
        function NbLines($w, $txt)
        {
            if (!isset($this->CurrentFont)) $this->Error('No font has been set');
            $cw   = $this->CurrentFont['cw'];
            if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s    = str_replace("\r", '', (string) $txt);
            $nb   = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
            $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
            while ($i < $nb) {
                $c = $s[$i];
                if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
                if ($c == ' ') $sep = $i;
                $l += $cw[$c];
                if ($l > $wmax) {
                    if ($sep == -1) { if ($i == $j) $i++; }
                    else $i = $sep + 1;
                    $sep = -1; $j = $i; $l = 0; $nl++;
                } else $i++;
            }
            return $nl;
        }

        // Línea horizontal punteada (separador estilo recibo web).
        function DashedRow($x1, $x2, $y, $dash = 1.1, $gap = 1.1)
        {
            $this->SetDrawColor(199, 204, 214);
            $this->SetLineWidth(0.2);
            $x = $x1;
            while ($x < $x2) { $xe = min($x + $dash, $x2); $this->Line($x, $y, $xe, $y); $x += $dash + $gap; }
        }
    }
}

function generarTicketPdf($pedidoId)
{
    $pedidoId = intval($pedidoId);

    // ===== Datos del pedido (mismos campos/queries que la vista web exTicket.php) =====
    // Cabecera + cliente. Se conserva el join b2b (clientes.codigo = pedidos.clienteId)
    // que ya usaba este PDF; sin match no se genera el comprobante.
    $cabRes = Connection::runQuery(
        "SELECT v.pedidoid, v.fecha, v.clienteId, c.codigo AS clienteCodigo,
                c.razonSocial AS cliente, c.direccion, c.localidad
         FROM pedidos v INNER JOIN clientes c ON c.codigo = v.clienteId
         WHERE v.pedidoid = $pedidoId
         ORDER BY v.id LIMIT 1"
    );
    $cab = $cabRes ? mysqli_fetch_assoc($cabRes) : null;
    if (!$cab) return null;

    // Ítems (join a articulos, igual que Venta::ventadetalles → mismas descripciones/códigos).
    $itemsRes = Connection::runQuery(
        "SELECT a.descripcion AS articulo, a.codigo, d.cantidad, d.precio,
                ROUND((d.cantidad * d.precio - d.descuento), 2) AS subtotal, d.dato9
         FROM pedidos d INNER JOIN articulos a ON d.producto = a.codigo
         WHERE d.pedidoid = $pedidoId
         ORDER BY d.id"
    );
    $items = [];
    while ($itemsRes && ($row = mysqli_fetch_assoc($itemsRes))) { $items[] = $row; }

    // Observaciones (líneas con producto '.001'), como la caja "Observación" de la web.
    $obsRes = Connection::runQuery(
        "SELECT descripcion FROM pedidos WHERE pedidoid = $pedidoId AND producto = '.001'"
    );
    $obs = [];
    while ($obsRes && ($row = mysqli_fetch_assoc($obsRes))) {
        if (trim((string) $row['descripcion']) !== '') $obs[] = $row['descripcion'];
    }

    // ===== Datos de la empresa — fuente de verdad: pedidos_platform.tenants =====
    // Igual que la vista web reportes/exTicket.php: nombre/razón/CUIT/teléfono salen de
    // `tenants` (configurables en Configuración → Empresa o en el superadmin); el logo es
    // un archivo local y vive en bot_config. ANTES salían de bot_config, que en muchos
    // tenants (p.ej. demo) está en NULL → el PDF no mostraba CUIT/Tel aunque la web sí.
    // El tenant se resuelve por Connection::getDatabase() (send_wa.php ya hizo setDatabase),
    // no por $_SESSION (en el flujo del bot no hay sesión).
    $empNombre = 'Atiende';
    $empRazon = $empCuit = $empTel = '';
    $tdb  = Connection::getDatabase();
    $slug = (strncmp($tdb, 'atiende_', 8) === 0) ? substr($tdb, 8) : $tdb;
    try {
        $ppT = new PDO('mysql:host=' . DB_HOST . ';dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stT = $ppT->prepare('SELECT nombre, razon_social, cuit, telefono FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
        $stT->execute([$slug]);
        $tEmp = $stT->fetch();
        if ($tEmp) {
            if (trim((string) ($tEmp['nombre'] ?? '')) !== '') $empNombre = $tEmp['nombre'];
            $empRazon = $tEmp['razon_social'] ?? '';
            $empCuit  = $tEmp['cuit'] ?? '';
            $empTel   = $tEmp['telefono'] ?? '';
        }
    } catch (Throwable $e) {
        error_log('[ticket_pdf] no se pudo leer empresa de tenants: ' . $e->getMessage());
    }

    // Logo (opcional, centrado arriba) — sigue en bot_config (files/empresa/). Defensivo ante
    // drift de schema: el seed mínimo no tiene la columna `logo` → degradar sin abortar el PDF.
    $empLogo = '';
    try {
        $logoRow = mysqli_fetch_assoc(Connection::runQuery("SELECT logo FROM bot_config LIMIT 1"));
        if ($logoRow) $empLogo = $logoRow['logo'] ?? '';
    } catch (Throwable $e) { $empLogo = ''; }

    // Logo (opcional, centrado arriba). Mismo origen que la vista web (files/empresa).
    $logoPath = ($empLogo !== '') ? __ROOT__ . '/files/empresa/' . $empLogo : '';
    $logoW = $logoH = 0;
    if ($logoPath !== '' && is_file($logoPath) && @getimagesize($logoPath) !== false) {
        $info  = getimagesize($logoPath);
        $ratio = ($info[1] > 0) ? $info[0] / $info[1] : 1;
        $logoH = 14;
        $logoW = $logoH * $ratio;
        if ($logoW > 46) { $logoW = 46; $logoH = $logoW / $ratio; }
    }

    // ===== Helpers de formato (idénticos a la vista web) =====
    $money = function ($n) { return '$ ' . number_format(floatval($n), 2, ',', '.'); };
    $dec   = function ($s) { return utf8_decode((string) $s); };

    // ===== Layout (mm) — recibo angosto de 80mm de ancho =====
    $W = 80; $ML = 6; $MR = 6; $uw = $W - $ML - $MR; // ancho útil = 68
    $TOP = 8;
    // Anchos de columnas de la tabla de ítems (suman $uw).
    $cCant = 10; $cCod = 13; $cImp = 15; $cDesc = $uw - $cCant - $cCod - $cImp; // 30
    // Altos de línea por bloque.
    $hName = 6; $hSmall = 3.6; $hDatos = 4.4; $hItem = 4; $hObsLine = 3.4;
    $hHead = 5; $hTotal = 7; $hMeta = 4.5; $hFootBig = 5; $hFootSmall = 4.5;

    // Detalle de empresa (cada línea por separado, como los <small> de la web).
    $detalle = [];
    if (trim($empRazon) !== '') $detalle[] = $empRazon;
    if (trim($empCuit)  !== '') $detalle[] = 'CUIT: ' . $empCuit;
    if (trim($empTel)   !== '') $detalle[] = 'Tel: ' . $empTel;

    // Filas del bloque de datos (label => valor), mismas que la web.
    $datos = [
        ['Fecha:',       $cab['fecha']],
        ['Código:',      $cab['clienteCodigo']],
        ['Cliente:',     $cab['cliente']],
        ['Dirección:',   $cab['direccion']],
        ['Localidad:',   $cab['localidad']],
        ['N° de venta:', $cab['pedidoid']],
    ];

    // ===== Paso 1: calcular el alto total (instancia de medición, no se imprime) =====
    $m = new TicketPDF('P', 'mm', array($W, 1000));
    $H = $TOP;
    if ($logoH > 0) $H += $logoH + 2;
    $m->SetFont('Helvetica', 'B', 12); $H += $m->NbLines($uw, $dec($empNombre)) * $hName;
    $m->SetFont('Helvetica', '', 7);   foreach ($detalle as $d) $H += $m->NbLines($uw, $dec($d)) * $hSmall;
    $H += 2 + 3; // gap + separador
    $m->SetFont('Helvetica', '', 8);
    foreach ($datos as $row) $H += max(1, $m->NbLines($uw - 22, $dec($row[1]))) * $hDatos;
    $H += 1 + 3;           // gap + separador
    $H += $hHead + 1;      // encabezado tabla + línea
    foreach ($items as $it) {
        $m->SetFont('Helvetica', '', 7.5);
        $H += max(1, $m->NbLines($cDesc, $dec(ucwords(strtolower($it['articulo']))))) * $hItem;
        if (strlen((string) $it['dato9']) > 0) {
            $m->SetFont('Helvetica', 'I', 6.5);
            $H += $m->NbLines($uw, $dec('Obs: ' . $it['dato9'])) * $hObsLine;
        }
    }
    $H += 2 + $hTotal;     // borde superior + fila TOTAL
    $H += $hMeta;          // N° de artículos
    if ($obs) {
        $H += 3 + 5;       // gap + título "Observación"
        $m->SetFont('Helvetica', '', 7);
        foreach ($obs as $o) $H += $m->NbLines($uw, $dec($o)) * 3.8;
    }
    $H += 5 + $hFootBig + $hFootSmall; // pie
    $H += 6;               // pad inferior de seguridad

    // ===== Paso 2: render =====
    $pdf = new TicketPDF('P', 'mm', array($W, $H));
    $pdf->SetMargins($ML, $TOP, $MR);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetTitle('Pedido ' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT));
    $pdf->SetAuthor('Atiende');
    $pdf->SetCreator('Atiende');
    $pdf->SetSubject('Comprobante de pedido N ' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT));
    $pdf->AddPage();

    // --- Empresa (centrado) ---
    if ($logoH > 0) {
        $pdf->Image($logoPath, ($W - $logoW) / 2, $pdf->GetY(), $logoW, $logoH);
        $pdf->SetY($pdf->GetY() + $logoH + 2);
    }
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(102, 80, 234); // violeta #6650EA
    $pdf->SetX($ML); $pdf->MultiCell($uw, $hName, $dec($empNombre), 0, 'C');
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(108, 117, 125);
    foreach ($detalle as $d) { $pdf->SetX($ML); $pdf->MultiCell($uw, $hSmall, $dec($d), 0, 'C'); }
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->DashedRow($ML, $ML + $uw, $pdf->GetY()); $pdf->SetY($pdf->GetY() + 3);

    // --- Datos ---
    $pdf->SetFont('Helvetica', '', 8);
    foreach ($datos as $row) {
        $y0 = $pdf->GetY();
        $pdf->SetTextColor(108, 117, 125);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY($ML, $y0); $pdf->Cell(22, $hDatos, $dec($row[0]), 0, 0, 'L');
        $pdf->SetTextColor(33, 37, 41);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY($ML + 22, $y0); $pdf->MultiCell($uw - 22, $hDatos, $dec($row[1]), 0, 'L');
    }
    $pdf->SetY($pdf->GetY() + 1);
    $pdf->DashedRow($ML, $ML + $uw, $pdf->GetY()); $pdf->SetY($pdf->GetY() + 3);

    // --- Tabla de ítems: encabezado ---
    $pdf->SetFont('Helvetica', 'B', 6.5);
    $pdf->SetTextColor(73, 80, 87);
    $pdf->SetX($ML);
    $pdf->Cell($cCant, $hHead, 'Cant.', 0, 0, 'L');
    $pdf->Cell($cCod,  $hHead, $dec('Cód.'), 0, 0, 'L');
    $pdf->Cell($cDesc, $hHead, $dec('Descripción'), 0, 0, 'L');
    $pdf->Cell($cImp,  $hHead, 'Importe', 0, 1, 'R');
    $yb = $pdf->GetY();
    $pdf->SetDrawColor(52, 58, 64); $pdf->SetLineWidth(0.4); $pdf->Line($ML, $yb, $ML + $uw, $yb);
    $pdf->SetY($yb + 1);

    // --- Ítems ---
    $total = 0.0; $cantTotal = 0;
    foreach ($items as $it) {
        $y0 = $pdf->GetY();
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->SetXY($ML, $y0);              $pdf->Cell($cCant, $hItem, $it['cantidad'], 0, 0, 'L');
        $pdf->SetXY($ML + $cCant, $y0);     $pdf->Cell($cCod, $hItem, $dec($it['codigo']), 0, 0, 'L');
        $pdf->SetXY($ML + $cCant + $cCod, $y0);
        $pdf->MultiCell($cDesc, $hItem, $dec(ucwords(strtolower($it['articulo']))), 0, 'L');
        $yEnd = $pdf->GetY();
        $pdf->SetXY($ML + $cCant + $cCod + $cDesc, $y0);
        $pdf->Cell($cImp, $hItem, $money($it['subtotal']), 0, 0, 'R');
        $pdf->SetY($yEnd);
        if (strlen((string) $it['dato9']) > 0) {
            $pdf->SetFont('Helvetica', 'I', 6.5);
            $pdf->SetTextColor(108, 117, 125);
            $pdf->SetX($ML); $pdf->MultiCell($uw, $hObsLine, $dec('Obs: ' . $it['dato9']), 0, 'L');
        }
        $total     += floatval($it['subtotal']);
        $cantTotal += intval($it['cantidad']);
    }

    // --- Total ---
    $pdf->SetY($pdf->GetY() + 2);
    $yt = $pdf->GetY();
    $pdf->SetDrawColor(52, 58, 64); $pdf->SetLineWidth(0.5); $pdf->Line($ML, $yt, $ML + $uw, $yt);
    $pdf->SetY($yt + 1.5);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->SetX($ML); $pdf->Cell($uw - $cImp - 6, $hTotal, 'TOTAL', 0, 0, 'L');
    $pdf->SetTextColor(10, 143, 87); // verde #0a8f57
    $pdf->Cell($cImp + 6, $hTotal, $money($total), 0, 1, 'R');

    // --- N° de artículos ---
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->SetX($ML); $pdf->Cell($uw, $hMeta, $dec('N° de artículos: ') . $cantTotal, 0, 1, 'L');

    // --- Observación (.001) ---
    if ($obs) {
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetFont('Helvetica', 'B', 6.5);
        $pdf->SetTextColor(73, 80, 87);
        $pdf->SetX($ML); $pdf->Cell($uw, 5, strtoupper($dec('Observación')), 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(33, 37, 41);
        foreach ($obs as $o) { $pdf->SetX($ML); $pdf->MultiCell($uw, 3.8, $dec($o), 0, 'L'); }
    }

    // --- Pie ---
    $pdf->SetY($pdf->GetY() + 5);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(73, 80, 87);
    $pdf->SetX($ML); $pdf->Cell($uw, $hFootBig, $dec('¡Gracias por su compra!'), 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->SetX($ML); $pdf->Cell($uw, $hFootSmall, 'Utilizamos Atiende', 0, 1, 'C');

    // ===== Salida a archivo temporal (sin cambios en el contrato con send_wa.php) =====
    $fechaArchivo  = date('Ymd_His', strtotime($cab['fecha']));
    $clienteNombre = preg_replace('/[^A-Za-z0-9\-]/', '_', $cab['cliente']);
    $filename = str_pad($pedidoId, 6, '0', STR_PAD_LEFT)
        . '-' . $cab['clienteId']
        . '-' . $clienteNombre
        . '-' . $fechaArchivo
        . '.pdf';

    $tmpFile = sys_get_temp_dir() . '/' . $filename;
    $pdf->Output('F', $tmpFile);
    return ['path' => $tmpFile, 'filename' => $filename];
}
