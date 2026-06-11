# Normalización de teléfono por país (multi-tenant) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que la normalización de números a formato `wa_id` dependa del país del tenant (no hardcodee `549`), soportando un conjunto de países de LatAm.

**Architecture:** Un helper puro `config/Telefono.php` centraliza la lógica (hoy duplicada en 3 lugares). El país del tenant se guarda en `bot_config.pais` (cache local, leído por la normalización) y en `pedidos_platform.tenants.pais` (fuente de verdad, seteado en la pestaña Empresa). Default `'AR'` en todo → los tenants existentes no cambian de comportamiento.

**Tech Stack:** PHP 8 sin framework, MySQL 8 (container `mysql8`, root/root), Docker (`atiende-app`, `app/` bind-montado en `/var/www/atiende`). **No hay PHPUnit** — los tests son scripts PHP planos con `assert`/exit-code, corridos con `docker exec atiende-app php …`.

**Spec:** `app/docs/superpowers/specs/2026-06-11-normalizacion-telefono-multipais-design.md`

**Rama:** `feat/menu-visibilidad-opciones` (actual).

---

## Convenciones del repo

- Lint: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/<ruta>`.
- DB: `MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "<sql>"`.
- Bash cwd puede derivar; prefijá con `cd /c/Users/ACER/Downloads/atiende 2>/dev/null;`.
- Editar en el host se refleja vivo en el container (bind mount).
- Tenants con bot: `SHOW DATABASES LIKE 'atiende%'` → `atiende_demo`, `atiende_corp` (la base `atiende` no tiene `bot_config`).
- **No tocar** bloques legacy Bootstrap 3 (`== 10`).

## File Structure

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `app/config/Telefono.php` | Crear | Helper puro: `Telefono::normalizar($numero, $pais)` + mapa de países |
| `app/tests/TelefonoNormalizarTest.php` | Crear | Test del helper (script plano, requiere la clase real) |
| `app/_docker/migrations/2026-06-11-pais-tenant.php` | Crear | Idempotente: `bot_config.pais` por tenant + `tenants.pais` central |
| `app/_docker/mariadb/bot_config_seed.sql` | Modificar | Columna `pais` en el `CREATE TABLE bot_config` |
| `app/ajax/vendedor.php` | Modificar | Usar el helper con el país del tenant |
| `app/ajax/configuracion.php` | Modificar | `saveAdminCopia` usa helper; `getEmpresa`/`guardarEmpresa` leen/escriben `pais` |
| `app/pedidos/finaliza.php` | Modificar | Usar el helper con el país del tenant |
| `app/vistas/configuracion.php` | Modificar | Dropdown País en la pestaña Empresa + JS |

---

## Task 1: Helper `config/Telefono.php` + test (TDD)

**Files:**
- Create: `app/config/Telefono.php`
- Create: `app/tests/TelefonoNormalizarTest.php`

- [ ] **Step 1: Escribir el test que falla** — `app/tests/TelefonoNormalizarTest.php`:

```php
<?php
// Test de Telefono::normalizar (script plano, sin framework, sin DB — la clase es pura).
// Correr: docker exec atiende-app php /var/www/atiende/tests/TelefonoNormalizarTest.php
require_once __DIR__ . '/../config/Telefono.php';

$casos = [
    // [numero, pais, esperado]
    ['3764278402',        'AR', '5493764278402'], // AR local → 549
    ['0376 4278402',      'AR', '5493764278402'], // AR con 0 troncal
    ['+54 9 376 4278402', 'AR', '5493764278402'], // AR ya internacional
    ['5493764278402',     'AR', '5493764278402'], // AR ya wa_id → tal cual
    ['0549376427840',     'AR', '549376427840'],  // AR zero-padded internacional → no duplica prefijo
    ['11987654321',       'BR', '5511987654321'], // BR local (ya con su 9) → solo prefijo
    ['5511987654321',     'BR', '5511987654321'], // BR ya internacional → tal cual
    ['5512345678',        'MX', '525512345678'],  // MX local 10 díg → solo prefijo
    ['099123456',         'UY', '59899123456'],   // UY con 0 troncal → solo prefijo
    ['',                  'AR', ''],              // vacío → vacío
    ['3764278402',        'ZZ', '5493764278402'], // país desconocido → AR
];

$fail = 0;
foreach ($casos as $i => $c) {
    $got = Telefono::normalizar($c[0], $c[1]);
    if ($got !== $c[2]) {
        fwrite(STDERR, "FAIL #$i: normalizar('{$c[0]}','{$c[1]}') = '$got' (esperado '{$c[2]}')\n");
        $fail++;
    }
}
if ($fail > 0) { fwrite(STDERR, "$fail caso(s) fallaron\n"); exit(1); }
echo "OK: " . count($casos) . " casos pasaron\n";
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php /var/www/atiende/tests/TelefonoNormalizarTest.php`
Expected: error (Failed opening required `config/Telefono.php` o `Class 'Telefono' not found`).

- [ ] **Step 3: Implementar `app/config/Telefono.php`**

```php
<?php
/**
 * Normalización de números de teléfono al formato wa_id según el país del tenant.
 * Lógica pura (sin DB) para que sea unit-testeable. Ver tests/TelefonoNormalizarTest.php.
 */
class Telefono
{
    /** cc = código de país; movil9 = inserta el '9' móvil (solo Argentina). */
    const PAISES = [
        'AR' => ['cc' => '54',  'movil9' => true],
        'BR' => ['cc' => '55'],
        'MX' => ['cc' => '52'],
        'UY' => ['cc' => '598'],
        'CL' => ['cc' => '56'],
        'PY' => ['cc' => '595'],
        'CO' => ['cc' => '57'],
        'PE' => ['cc' => '51'],
    ];

    /** Normaliza un número tipeado a formato wa_id según el país (ISO-2). Vacío → ''. */
    public static function normalizar($numero, $pais = 'AR'): string
    {
        $d = preg_replace('/\D/', '', (string) $numero);
        $d = ltrim($d, '0');                                  // troncal local (0…) primero → evita prefijo duplicado
        if ($d === '') return '';
        $p  = self::PAISES[$pais] ?? self::PAISES['AR'];       // país desconocido → AR (retrocompat)
        $cc = $p['cc'];
        if (strncmp($d, $cc, strlen($cc)) === 0) return $d;   // ya trae código de país → tal cual
        return !empty($p['movil9']) ? $cc . '9' . $d : $cc . $d;
    }
}
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php /var/www/atiende/tests/TelefonoNormalizarTest.php`
Expected: `OK: 11 casos pasaron`. (Si algún caso falla, ajustar el helper, NO el test, salvo que el caso esperado sea incorrecto.)

- [ ] **Step 5: Lint + Commit**

```bash
cd /c/Users/ACER/Downloads/atiende 2>/dev/null
MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/config/Telefono.php
git add app/config/Telefono.php app/tests/TelefonoNormalizarTest.php
git commit -m "feat(telefono): helper Telefono::normalizar por país + test unitario"
```

---

## Task 2: Migración `bot_config.pais` + `tenants.pais` + seed

**Files:**
- Create: `app/_docker/migrations/2026-06-11-pais-tenant.php`
- Modify: `app/_docker/mariadb/bot_config_seed.sql`

- [ ] **Step 1: Crear el script de migración idempotente**

```php
<?php
// Idempotente: agrega bot_config.pais (en la DB del tenant pasada) y tenants.pais (DB central, una vez).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-11-pais-tenant.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }
$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }

// 1) bot_config.pais en la DB del tenant (si la tabla existe y la columna no)
if ($m->query("SHOW TABLES LIKE 'bot_config'")->num_rows > 0) {
    if ($m->query("SHOW COLUMNS FROM `bot_config` LIKE 'pais'")->num_rows === 0) {
        $m->query("ALTER TABLE `bot_config` ADD COLUMN `pais` VARCHAR(2) NOT NULL DEFAULT 'AR'");
        echo "[$db] + bot_config.pais\n";
    } else { echo "[$db] = bot_config.pais (ya existe)\n"; }
} else { echo "[$db] sin tabla bot_config\n"; }

// 2) tenants.pais en la DB central pedidos_platform (idempotente, una sola vez)
$pp = new mysqli('mysql8', 'root', 'root', 'pedidos_platform');
if (!$pp->connect_errno) {
    if ($pp->query("SHOW COLUMNS FROM `tenants` LIKE 'pais'")->num_rows === 0) {
        $pp->query("ALTER TABLE `tenants` ADD COLUMN `pais` VARCHAR(2) NOT NULL DEFAULT 'AR'");
        echo "[pedidos_platform] + tenants.pais\n";
    } else { echo "[pedidos_platform] = tenants.pais (ya existe)\n"; }
    $pp->close();
}
$m->close();
```

- [ ] **Step 2: Ejecutar en demo y corp; verificar**

Run:
```
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-11-pais-tenant.php atiende_demo
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-11-pais-tenant.php atiende_corp
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SELECT pais FROM atiende_demo.bot_config WHERE id=1; SHOW COLUMNS FROM pedidos_platform.tenants LIKE 'pais';"
```
Expected: `bot_config.pais` y `tenants.pais` creados (default `AR`); el SELECT devuelve `AR`. Re-correr = `= … (ya existe)` (idempotente).

- [ ] **Step 3: Agregar `pais` al CREATE TABLE del seed**

En `app/_docker/mariadb/bot_config_seed.sql`, dentro del `CREATE TABLE IF NOT EXISTS \`bot_config\``, agregar la columna (p.ej. después de `telefono`):
```sql
  `pais`               varchar(2)     NOT NULL DEFAULT 'AR',
```

- [ ] **Step 4: Validar el seed contra una DB limpia y limpiar**

Run:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS _paischk; CREATE DATABASE _paischk;"
MSYS_NO_PATHCONV=1 docker exec -i mysql8 mysql -uroot -proot _paischk < app/_docker/mariadb/bot_config_seed.sql
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SELECT pais FROM _paischk.bot_config WHERE id=1;"
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE _paischk;"
```
Expected: `AR`.

- [ ] **Step 5: Commit**

```bash
cd /c/Users/ACER/Downloads/atiende 2>/dev/null
git add app/_docker/migrations/2026-06-11-pais-tenant.php app/_docker/mariadb/bot_config_seed.sql
git commit -m "feat(telefono): migración bot_config.pais + tenants.pais + seed"
```

---

## Task 3: Refactor de los 3 puntos de normalización

**Files:**
- Modify: `app/ajax/vendedor.php`
- Modify: `app/ajax/configuracion.php` (`saveAdminCopia`)
- Modify: `app/pedidos/finaliza.php`

> Helper de lectura del país: cada punto lee `bot_config.pais` con la conexión que ya tiene, con fallback `'AR'` (tolera bot_config ausente).

- [ ] **Step 1: `ajax/vendedor.php`**

Agregar el require arriba (junto a los otros require):
```php
require_once __ROOT__ . '/config/Telefono.php';
```
Reemplazar el bloque de normalización actual (el que hace `preg_replace` + `ltrim` + `if (!startsWith '54') '549'…`) dentro de `case 'guardaryeditar':` por:
```php
	// Normalizar el teléfono al formato wa_id según el país del tenant (bot_config.pais).
	$pais = 'AR';
	$rp = ejecutarConsultaSimpleFila("SELECT pais FROM bot_config LIMIT 1");
	if (is_array($rp) && !empty($rp['pais'])) { $pais = $rp['pais']; }
	$telefono = Telefono::normalizar($telefono, $pais);
```
(Si `ejecutarConsultaSimpleFila` no existe con ese nombre exacto, usar el helper de lectura simple que ya usa el modelo `Vendedor`; el objetivo es leer `bot_config.pais` con fallback `'AR'`.)

- [ ] **Step 2: `ajax/configuracion.php` → `saveAdminCopia`**

Agregar require arriba del archivo si no está:
```php
require_once __DIR__ . '/../config/Telefono.php';
```
Reemplazar la normalización inline de `$tel` (el `ltrim` + `if (!startsWith '54') '549'…`) por:
```php
        $pais = 'AR';
        $rpais = mysqli_query($conexion, "SELECT pais FROM bot_config LIMIT 1");
        if ($rpais && ($rr = mysqli_fetch_assoc($rpais)) && !empty($rr['pais'])) { $pais = $rr['pais']; }
        $tel = Telefono::normalizar($tel, $pais);
```
(Mantener el `$tel = preg_replace('/\D/', '', …)` previo si está, o dejar que el helper lo haga; no duplicar.)

- [ ] **Step 3: `pedidos/finaliza.php`**

Agregar arriba (junto al `include_once` de Connection):
```php
include_once("../config/Telefono.php");
```
Reemplazar:
```php
    $telefono = (strncmp($digits, '54', 2) === 0) ? $digits : ('549' . ltrim($digits, '0'));
```
por:
```php
    $paisT = 'AR';
    $rpais = Connection::runQuery("SELECT pais FROM bot_config LIMIT 1");
    if ($rpais && ($rr = mysqli_fetch_assoc($rpais)) && !empty($rr['pais'])) { $paisT = $rr['pais']; }
    $telefono = Telefono::normalizar($digits, $paisT);
```

- [ ] **Step 4: Lint los 3 archivos**

Run:
```
MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/ajax/vendedor.php
MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/ajax/configuracion.php
MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/pedidos/finaliza.php
```
Expected: `No syntax errors detected` en los 3.

- [ ] **Step 5: Verificar que AR no regresiona**

Run (un vendedor con número local debe quedar `549…` en demo, que es AR):
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "UPDATE atiende_demo.bot_config SET pais='AR' WHERE id=1;"
```
Luego, prueba manual: en `vendedor.php` editar un vendedor con teléfono `3764111111` → guardar → en DB debe quedar `5493764111111`.

- [ ] **Step 6: Commit**

```bash
cd /c/Users/ACER/Downloads/atiende 2>/dev/null
git add app/ajax/vendedor.php app/ajax/configuracion.php app/pedidos/finaliza.php
git commit -m "refactor(telefono): los 3 puntos de normalización usan Telefono::normalizar + país del tenant"
```

---

## Task 4: Pestaña Empresa — `pais` (backend + dropdown)

**Files:**
- Modify: `app/ajax/configuracion.php` (`getEmpresa`, `guardarEmpresa`)
- Modify: `app/vistas/configuracion.php` (form + JS de la pestaña Empresa)

- [ ] **Step 1: `getEmpresa` (ajax) — incluir `pais`**

- Agregar `'pais' => 'AR'` al struct de fallback `$emp` (`['nombre'=>'', 'razon_social'=>'', 'cuit'=>'', 'telefono'=>'', 'pais'=>'AR']`).
- Cambiar el SELECT a `SELECT nombre, razon_social, cuit, telefono, pais FROM tenants WHERE slug = ? …`.
- Agregar `'pais' => $emp['pais'] ?? 'AR'` al `echo json_encode([...])`.

- [ ] **Step 2: `guardarEmpresa` (ajax) — escribir `pais` en tenants + ambas ramas de bot_config**

- Leer del POST: `$pais = strtoupper(trim($_POST['pais'] ?? 'AR')); if (!isset(Telefono::PAISES[$pais])) $pais = 'AR';` (validar contra el mapa; agregar el `require_once` de Telefono si no está).
- `UPDATE tenants`: `… SET nombre = ?, razon_social = ?, cuit = ?, telefono = ?, pais = ? WHERE slug = ? …` y agregar `$pais` a los `execute([...])` antes de `$slug`.
- `UPDATE bot_config` (las **dos** ramas, con y sin logo): agregar `, pais = ?` al SET y el bind correspondiente. Ej. rama sin logo:
  ```php
  $stmt = $conexion->prepare("UPDATE bot_config SET telefono = ?, pais = ? WHERE id = 1");
  $stmt->bind_param('ss', $telefono, $pais);
  ```
  Rama con logo:
  ```php
  $stmt = $conexion->prepare("UPDATE bot_config SET logo = ?, telefono = ?, pais = ? WHERE id = 1");
  $stmt->bind_param('sss', $logoName, $telefono, $pais);
  ```

- [ ] **Step 3: Dropdown País en el form (vista)**

En `app/vistas/configuracion.php`, dentro del `<div class="row g-3">` de la pestaña Empresa (`#tab-empresa`), agregar una columna con el select (p.ej. después del Teléfono, `col-md-5`):
```html
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">País</label>
                                        <select class="form-select" id="empPais">
                                            <option value="AR">Argentina</option>
                                            <option value="BR">Brasil</option>
                                            <option value="MX">México</option>
                                            <option value="UY">Uruguay</option>
                                            <option value="CL">Chile</option>
                                            <option value="PY">Paraguay</option>
                                            <option value="CO">Colombia</option>
                                            <option value="PE">Perú</option>
                                        </select>
                                        <small class="text-muted d-block mt-1">Define cómo se normalizan los números de WhatsApp del negocio.</small>
                                    </div>
```

- [ ] **Step 4: JS — cargar y guardar `pais`**

- En `cargarEmpresa()` (success de getEmpresa), agregar:
  ```js
  document.getElementById('empPais').value = r.pais || 'AR';
  ```
- En `guardarEmpresa()`, agregar al FormData:
  ```js
  fd.append('pais', document.getElementById('empPais').value);
  ```

- [ ] **Step 5: Lint + prueba manual**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/ajax/configuracion.php` y `… php -l /var/www/atiende/vistas/configuracion.php` → `No syntax errors detected`.

Prueba manual (en `configuracion.php` → Empresa): cambiar País a Brasil, Guardar; recargar y verificar que queda Brasil. En DB:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SELECT pais FROM atiende_demo.bot_config WHERE id=1; SELECT pais FROM pedidos_platform.tenants WHERE slug='demo';"
```
Expected: ambos `BR`. **Volver a dejar `AR`** después de la prueba (demo es AR):
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "UPDATE atiende_demo.bot_config SET pais='AR' WHERE id=1; UPDATE pedidos_platform.tenants SET pais='AR' WHERE slug='demo';"
```

- [ ] **Step 6: Commit**

```bash
cd /c/Users/ACER/Downloads/atiende 2>/dev/null
git add app/ajax/configuracion.php app/vistas/configuracion.php
git commit -m "feat(empresa): selector de país del tenant (tenants.pais + bot_config.pais)"
```

---

## Cierre

- [ ] Actualizar `app/CLAUDE.md`: normalización de teléfono por país (helper `config/Telefono.php`, `bot_config.pais`/`tenants.pais`, default AR, mapa LatAm, los 3 puntos refactorizados, el `9` solo para AR). Commit.
- [ ] Aplicar la migración a cualquier otro tenant con bot que aparezca en `SHOW DATABASES LIKE 'atiende%'`.
- [ ] Revisar el diff con `superpowers:requesting-code-review` antes de mergear.

## Notas de riesgo

- **Default `'AR'` en todo** → los tenants existentes (todos AR hoy) no cambian de comportamiento. El branch AR del helper reproduce exacto la lógica inline previa.
- **Quirks legacy de BR/MX** (números viejos con/sin el dígito de móvil que cambió Meta) están fuera de alcance.
- **`bot_config` ausente** (DB base `atiende`): las lecturas de `pais` tienen fallback `'AR'`; ese código corre siempre en contexto de un tenant real, no de la base.
- El test del helper requiere la **clase real** (no una copia), así que no hay que mantener un duplicado (a diferencia de `BotEngineProyectarMenuTest`).
