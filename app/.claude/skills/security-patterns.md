---
name: security-patterns
description: Use when writing any endpoint, form, file upload, webhook or view. Covers auth checks, CSRF, XSS escaping, IDOR, file upload and session security for this project.
---

# Patrones de Seguridad — Atiende

## Auth check — obligatorio en todo ajax/ y axadmin/

Todo archivo en `ajax/` debe empezar con:
```php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}
```

## CSRF — todo POST en ajax/

Generar token en login:
```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

Verificar en cada POST:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'CSRF inválido']);
        exit;
    }
}
```

Incluir en formularios JS:
```javascript
data: { csrf_token: '<?php echo $_SESSION["csrf_token"]; ?>', ... }
```

## XSS — escapar todo output en HTML

```php
// ❌ VULNERABLE
echo $row['razonSocial'];

// ✅ SEGURO
echo htmlspecialchars($row['razonSocial'], ENT_QUOTES, 'UTF-8');
```

Excepciones: JSON via `json_encode` (ya escapa), o datos numéricos verificados como int/float.

## IDOR — verificar ownership antes de leer/modificar

```php
// ❌ VULNERABLE: cualquier usuario puede pedir cualquier pedido
$row = ejecutarConsulta("SELECT * FROM pedidos WHERE pedidoid = ".$_POST['id']);

// ✅ SEGURO: verificar que pertenece al tenant activo
$stmt = $conexion->prepare("SELECT * FROM pedidos WHERE pedidoid = ? AND clienteId IN (SELECT codigo FROM clientes)");
```

Regla: si el recurso tiene un dueño (tenant, cliente, vendedor), siempre filtrar por el contexto de sesión.

## Subida de archivos

```php
$allowed_ext = ['xlsx', 'xls', 'csv'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_ext)) {
    echo json_encode(['ok' => false, 'error' => 'Extensión no permitida']);
    exit;
}
// Renombrar con nombre aleatorio
$newName = bin2hex(random_bytes(16)) . '.' . $ext;
move_uploaded_file($_FILES['file']['tmp_name'], '../files/' . $newName);
```

- Nunca ejecutar archivos subidos — guardar fuera del webroot o en directorio sin ejecución PHP
- Validar también el MIME type con `finfo_file()` para imágenes

## Webhook WhatsApp — verificar firma HMAC

```php
$payload    = file_get_contents('php://input');
$signature  = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected   = 'sha256=' . hash_hmac('sha256', $payload, WA_APP_SECRET);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}
```

`hash_equals()` es obligatorio para evitar timing attacks.

## Sesiones

- `session_regenerate_id(true)` inmediatamente después de login exitoso
- En producción, cookies con:
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
```

## Errores — nunca exponer al usuario

```php
// ❌
echo $e->getMessage();

// ✅
error_log('[ERROR] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
echo json_encode(['ok' => false, 'error' => 'Error interno. Intentá nuevamente.']);
```

## Credenciales

- Solo en `config/database.php` y `config/global.php` (git-ignored)
- Nunca en código fuente, comentarios ni variables JS
