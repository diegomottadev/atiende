---
name: api-design
description: Use when creating or modifying any endpoint in ajax/, axadmin/api/ or pedidos/. Covers response format, validation, pagination, exports and rate limiting for this project.
---

# Diseño de Endpoints — Atiende

## Formato de respuesta estándar

```php
// Éxito
echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);

// Error de validación
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'El campo telefono es requerido']);

// No autenticado
http_response_code(401);
echo json_encode(['ok' => false, 'error' => 'Unauthorized']);

// Error interno
http_response_code(500);
echo json_encode(['ok' => false, 'error' => 'Error interno. Intentá nuevamente.']);
```

## Estructura de un handler en ajax/

```php
<?php
// 1. Auth check
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

// 2. Include
require_once '../config/Conexion.php';
require_once '../modelos/MiModelo.php';

// 3. Leer y validar input
$accion = $_POST['accion'] ?? '';
switch ($accion) {
    case 'listar':
        // validar parámetros opcionales
        break;
    case 'guardar':
        if (empty($_POST['nombre'])) { echo json_encode(['ok'=>false,'error'=>'Nombre requerido']); exit; }
        break;
    default:
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Acción desconocida']);
        exit;
}
```

## Validación de input

Validar presencia Y tipo antes de ejecutar lógica:
```php
$id    = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$id || !$email) {
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
    exit;
}
```

## Paginación — DataTables server-side

```php
$start  = (int)($_POST['start']  ?? 0);
$length = (int)($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// ... query con LIMIT y COUNT ...

echo json_encode([
    'draw'            => (int)($_POST['draw'] ?? 1),
    'recordsTotal'    => $total,
    'recordsFiltered' => $filtered,
    'data'            => $rows,
], JSON_UNESCAPED_UNICODE);
```

## Exports (Excel / PDF)

```php
// No mezclar con endpoints JSON — archivo separado
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte.xlsx"');
// ... generar con PHPExcel/PhpSpreadsheet ...
```

Botón de export **fuera** del card, en `row mb-2 mt-n4` encima del card.

## WhatsApp send (ajax/send_wa.php)

- Leer `type` antes de cualquier validación
- Solo `text` es requerido para tipos no-location (location no tiene text)
- Devolver el array real del cliente WA para que el JS muestre éxito/error correcto
- Verificar que el pedido/recurso pertenece al tenant antes de enviar

## Idempotencia

Operaciones de provisioning y webhooks:
```php
// INSERT IGNORE para evitar duplicados
ejecutarConsulta("INSERT IGNORE INTO eventos (id, tipo) VALUES ('$id', '$tipo')");
if ($conexion->affected_rows === 0) {
    // Ya procesado — devolver OK sin re-ejecutar
    echo json_encode(['ok' => true, 'data' => 'already_processed']);
    exit;
}
```

## Rate limiting básico

Antes de enviar mensaje WA, verificar que el recurso pertenece al tenant:
```php
$pedido = $conexion->query("SELECT pedidoid FROM pedidos WHERE pedidoid = ? AND ...")->fetch_assoc();
if (!$pedido) {
    echo json_encode(['ok' => false, 'error' => 'Recurso no encontrado']);
    exit;
}
```
