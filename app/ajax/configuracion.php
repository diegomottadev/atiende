<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/auth.php';
require (__ROOT__.'/config/Conexion.php');

header('Content-Type: application/json');

$op = $_GET['op'] ?? '';

// Solo usuarios con el permiso 'Configuración' (permiso 11) pueden operar acá.
// Protege especialmente getToken/saveToken, que exponen/escriben credenciales de WhatsApp.
if (empty($_SESSION['configuracion'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sin permiso para acceder a Configuración']);
    exit;
}

csrfGuard($op, ['listarAreas', 'listarReclamos', 'listarConsultas', 'getMenuPrincipal', 'listarAreasAdmin', 'getToken']);

switch ($op) {

    case 'listarAreas':
        $res  = ejecutarConsulta("SELECT id, area FROM areas_consultas WHERE activo=1 ORDER BY area");
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode($data);
        break;

    case 'listarReclamos':
        $res  = ejecutarConsulta("SELECT m.id, m.opcionId, m.opcion, m.area, a.area AS areaNombre
                                  FROM motivo_reclamos m
                                  LEFT JOIN areas_consultas a ON a.id = m.area
                                  ORDER BY m.id");
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['data' => $data]);
        break;

    case 'listarConsultas':
        $res  = ejecutarConsulta("SELECT m.id, m.opcionId, m.opcion, m.area, a.area AS areaNombre
                                  FROM motivo_consultas m
                                  LEFT JOIN areas_consultas a ON a.id = m.area
                                  ORDER BY m.id");
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['data' => $data]);
        break;

    case 'guardar':
        $tabla  = $_POST['tabla']  ?? '';
        $id     = intval($_POST['id']    ?? 0);
        $opcion = limpiarCadena($_POST['opcion'] ?? '');
        $area   = intval($_POST['area']   ?? 1);

        if ($tabla === 'reclamos') {
            $tbl    = 'motivo_reclamos';
            $menuId = '5';
        } elseif ($tabla === 'consultas') {
            $tbl    = 'motivo_consultas';
            $menuId = '15';
        } else {
            echo json_encode(['ok' => false, 'error' => 'tabla invalida']);
            break;
        }

        if ($id > 0) {
            $ok = ejecutarConsulta("UPDATE `$tbl` SET opcion='$opcion', area='$area' WHERE id=$id");
        } else {
            $maxRow = ejecutarConsultaSimpleFila("SELECT MAX(CAST(opcionId AS UNSIGNED)) AS maxId FROM `$tbl`");
            $nextId = intval($maxRow['maxId'] ?? 0) + 1;
            $ok     = ejecutarConsulta("INSERT INTO `$tbl` (opcionId, opcion, menuId, guardar, area) VALUES ('$nextId','$opcion','$menuId',0,'$area')");
        }
        echo json_encode(['ok' => (bool)$ok]);
        break;

    case 'eliminar':
        $tabla = $_POST['tabla'] ?? '';
        $id    = intval($_POST['id'] ?? 0);
        if ($id > 0 && in_array($tabla, ['reclamos', 'consultas'], true)) {
            $tbl = $tabla === 'reclamos' ? 'motivo_reclamos' : 'motivo_consultas';
            ejecutarConsulta("DELETE FROM `$tbl` WHERE id=$id");
            $rows = ejecutarConsulta("SELECT id FROM `$tbl` ORDER BY id");
            $n = 1;
            while ($r = $rows->fetch_assoc()) {
                ejecutarConsulta("UPDATE `$tbl` SET opcionId='$n' WHERE id=" . $r['id']);
                $n++;
            }
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false]);
        }
        break;

    case 'getMenuPrincipal':
        $editable = [
            '200' => 'Menú principal (clientes registrados)',
            '100' => 'Identificación de cliente (primera vez)',
        ];
        $allMenus = [];
        $res = mysqli_query($conexion, "SELECT menu_json FROM bot_config LIMIT 1");
        if ($res && ($jrow = mysqli_fetch_assoc($res))) {
            $decoded  = json_decode($jrow['menu_json'], true);
            $allMenus = isset($decoded['menu']) ? $decoded['menu'] : (is_array($decoded) ? $decoded : []);
        }
        $groups = [];
        foreach ($allMenus as $entry) {
            $mid = $entry['menuId'] ?? '';
            if (!isset($editable[$mid])) continue;
            $items = [];
            foreach (($entry['menuItem'] ?? []) as $opt) {
                if (!empty($opt['opcionId'])) {
                    $items[] = ['opcionId' => $opt['opcionId'], 'opcion' => $opt['opcion']];
                }
            }
            $groups[] = ['menuId' => $mid, 'label' => $editable[$mid], 'items' => $items];
        }
        usort($groups, function($a, $b) use ($editable) {
            return array_search($a['menuId'], array_keys($editable)) - array_search($b['menuId'], array_keys($editable));
        });
        echo json_encode($groups);
        break;

    case 'saveMenuPrincipal':
        $incoming     = json_decode($_POST['items'] ?? '[]', true);
        $targetMenuId = $_POST['menuId'] ?? '';
        $map          = [];
        foreach ($incoming as $it) $map[$it['opcionId']] = $it['opcion'];

        $res  = mysqli_query($conexion, "SELECT menu_json FROM bot_config LIMIT 1");
        $jrow = $res ? mysqli_fetch_assoc($res) : null;
        if (!$jrow) { echo json_encode(['ok' => false, 'error' => 'tenant sin menú']); break; }
        $decoded = json_decode($jrow['menu_json'], true);
        if (!is_array($decoded)) { echo json_encode(['ok'=>false,'error'=>'menú inválido']); break; }
        $wrapped = isset($decoded['menu']);
        $menuArr = $wrapped ? $decoded['menu'] : $decoded;
        $found = false;
        foreach ($menuArr as &$entry) {
            if (($entry['menuId'] ?? '') === $targetMenuId) {
                $found = true;
                foreach ($entry['menuItem'] as &$opt) {
                    if (isset($map[$opt['opcionId']])) { $opt['opcion'] = $map[$opt['opcionId']]; }
                }
                unset($opt);
                break;
            }
        }
        unset($entry);
        if (!$found) { echo json_encode(['ok'=>false,'error'=>'menú no encontrado']); break; }
        if ($wrapped) { $decoded['menu'] = $menuArr; } else { $decoded = $menuArr; }
        $newJson    = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        $newJsonEsc = mysqli_real_escape_string($conexion, $newJson);
        $ok = mysqli_query($conexion, "UPDATE bot_config SET menu_json = '$newJsonEsc' WHERE id = 1");
        echo json_encode(['ok' => (bool)$ok]);
        break;

    case 'listarAreasAdmin':
        $res  = ejecutarConsulta("SELECT id, area, telefono, activo FROM areas_consultas ORDER BY area");
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['data' => $data]);
        break;

    case 'guardarArea':
        $id       = intval($_POST['id']       ?? 0);
        $area     = limpiarCadena($_POST['area']     ?? '');
        $telefono = limpiarCadena($_POST['telefono'] ?? '');
        $activo   = intval($_POST['activo']   ?? 1);
        if (!$area || !$telefono) {
            echo json_encode(['ok' => false, 'error' => 'datos incompletos']);
            break;
        }
        if ($id > 0) {
            $ok = ejecutarConsulta("UPDATE areas_consultas SET area='$area', telefono='$telefono', activo=$activo WHERE id=$id");
        } else {
            $ok = ejecutarConsulta("INSERT INTO areas_consultas (area, telefono, activo) VALUES ('$area','$telefono',$activo)");
        }
        echo json_encode(['ok' => (bool)$ok]);
        break;

    case 'eliminarArea':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            ejecutarConsulta("DELETE FROM areas_consultas WHERE id=$id");
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false]);
        }
        break;

    case 'getToken':
        // Devuelve las credenciales WhatsApp del tenant actual (descifradas) para mostrarlas enmascaradas en la UI
        $slug = (strncmp($_SESSION['tenant_db'] ?? '', 'atiende_', 8) === 0)
            ? substr($_SESSION['tenant_db'], 8) : ($_SESSION['tenant_db'] ?? '');
        try {
            $pp = new PDO('mysql:host=' . DB_HOST . ';dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $st = $pp->prepare('SELECT whatsapp_phone_id, whatsapp_token_enc, whatsapp_app_secret_enc FROM tenants WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
            $st->execute([$slug]);
            $row = $st->fetch();
            $key = hex2bin(PLATFORM_ENCRYPTION_KEY);
            $dec = function ($enc) use ($key) {
                if (!$enc) return '';
                $d = base64_decode($enc);
                $v = openssl_decrypt(substr($d, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($d, 0, 12), substr($d, 12, 16));
                return $v === false ? '' : $v;
            };
            echo json_encode([
                'ok'        => true,
                'phoneId'   => $row ? ($row['whatsapp_phone_id'] ?? '') : '',
                'token'     => $row ? $dec($row['whatsapp_token_enc']) : '',
                'appSecret' => $row ? $dec($row['whatsapp_app_secret_enc']) : '',
            ]);
        } catch (Exception $e) {
            error_log('[configuracion] ' . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Error interno']);
        }
        break;

    case 'saveToken':
        $token     = trim($_POST['token']     ?? '');
        $appSecret = trim($_POST['appSecret'] ?? '');
        $phoneId   = trim($_POST['phoneId']   ?? '');
        if (!$token && !$appSecret && $phoneId === '') {
            echo json_encode(['ok' => false, 'error' => 'sin datos']);
            break;
        }
        $slug = (strncmp($_SESSION['tenant_db'] ?? '', 'atiende_', 8) === 0)
            ? substr($_SESSION['tenant_db'], 8) : ($_SESSION['tenant_db'] ?? '');
        try {
            $key = hex2bin(PLATFORM_ENCRYPTION_KEY);
            $enc = function ($plain) use ($key) {
                $iv = random_bytes(12); $tag = '';
                $ct = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
                return base64_encode($iv . $tag . $ct);
            };
            $pp = new PDO('mysql:host=' . DB_HOST . ';dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $sets = []; $params = [];
            if ($phoneId !== '') { $sets[] = 'whatsapp_phone_id = ?';        $params[] = $phoneId; }
            if ($token)     { $sets[] = 'whatsapp_token_enc = ?';      $params[] = $enc($token); }
            if ($appSecret) { $sets[] = 'whatsapp_app_secret_enc = ?'; $params[] = $enc($appSecret); }
            $params[] = $slug;
            $st = $pp->prepare('UPDATE tenants SET ' . implode(', ', $sets) . ' WHERE slug = ?');
            $st->execute($params);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            error_log('[configuracion] ' . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Error interno']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'op desconocida']);
}
