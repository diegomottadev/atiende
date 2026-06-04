---
name: db-patterns
description: Use when writing SQL queries, models, or any database interaction. Covers prepared statements, multi-tenant isolation, transactions and naming for this project.
---

# Patrones de Base de Datos — Atiende

## Regla principal: nunca concatenar input del usuario en SQL

```php
// ❌ VULNERABLE
$sql = "SELECT * FROM pedidos WHERE clienteId = '".$_POST['id']."'";

// ✅ SEGURO — PDO
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE clienteId = ?");
$stmt->execute([$_POST['id']]);

// ✅ SEGURO — MySQLi bind
$stmt = $conexion->prepare("SELECT * FROM pedidos WHERE clienteId = ?");
$stmt->bind_param("s", $_POST['id']);
$stmt->execute();
```

## Cuándo usar qué

| Situación | Usar |
|---|---|
| Query sin input del usuario | `ejecutarConsulta($sql)` de Conexion.php |
| Query con input del usuario | PDO `prepare/execute` o MySQLi `prepare/bind_param` |
| Query en flujo sin sesión (pedidos/) | PDO directo con `DB_HOST`, `DB_USERNAME`, etc. |
| Login del tenant | `ajax/usuario.php?op=verificar` (autentica contra `pedidos_platform.tenants`) |

## Multi-tenant: siempre operar sobre el tenant activo

- La conexión global `$conexion` ya apunta a `$_SESSION['tenant_db']` (seteada en Conexion.php)
- Nunca hardcodear nombre de DB en queries
- Al usar PDO inline, construir el DSN con `DB_HOST` y la DB correcta:
```php
$db = $_SESSION['tenant_db'] ?? DB_NAME;
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=$db;charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
```

## ORDER BY con input del usuario

No se puede usar prepared statement en ORDER BY — usar whitelist:

```php
$allowed = ['fecha', 'total', 'razonSocial'];
$col = in_array($_GET['sort'], $allowed) ? $_GET['sort'] : 'fecha';
$dir = $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$sql = "SELECT * FROM pedidos ORDER BY $col $dir";
```

## Transactions

Usar cuando hay múltiples escrituras relacionadas que deben ser atómicas:

```php
$conexion->begin_transaction();
try {
    ejecutarConsulta("INSERT INTO pedidos ...");
    ejecutarConsulta("UPDATE link_pedidos SET estado=1 ...");
    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    throw $e;
}
```

## Soft delete

Entidades críticas (tenants, usuarios, pedidos históricos) usan `deleted_at`:
```sql
UPDATE tenants SET deleted_at = NOW() WHERE id = ?
-- Al leer: WHERE deleted_at IS NULL
```

## Naming de tablas

- `snake_case`, plural: `link_pedidos`, `area_consultas`, `motivo_consultas`
- FK: `{tabla_singular}Id` → `clienteId`, `vendedorId`
- Toda FK debe tener índice; columnas de WHERE frecuente también

## Liberar resultsets

En loops que procesan muchos resultados:
```php
$result = ejecutarConsulta($sql);
while ($row = $result->fetch_assoc()) { ... }
$result->free();
```
