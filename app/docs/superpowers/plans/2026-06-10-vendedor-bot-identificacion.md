# Identificación de vendedor en el bot + CUIL/DNI en clientes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir que un vendedor se identifique en el bot de WhatsApp con su código de vendedor y cargue pedidos por sus clientes (con reintento y "Salir"), y agregar campos CUIL/DNI opcionales al ABM de clientes.

**Architecture:** El bot (`BotEngine.php`) gana dos menús de captura (105 código de vendedor, 106 código de cliente) y dos acciones (`registraVendedor`, `chequearVendedorCliente` reescrita). La identidad de vendedor se persiste en una columna nueva `contactos.vendedor_codigo` (sin pisar `vendedores.telefono`, que es el contacto real). El ABM de clientes (`Persona.php`/`ajax/persona.php`/`vistas/cliente.php`) gana dos columnas opcionales.

**Tech Stack:** PHP 8 (sin framework), MySQL 8 (container `mysql8`, root/root), Docker (app en `atiende-app`, root del repo bind-montado en `/var/www/atiende`). `menu_json` vive en `bot_config.menu_json` por tenant. **No hay test suite** (CLAUDE.md): la verificación es `php -l` + roundtrip de DB + E2E manual por WhatsApp. El motor es DB-coupled, no unit-testeable de forma aislada.

**Spec:** `app/docs/superpowers/specs/2026-06-10-vendedor-bot-identificacion-design.md`

**Rama:** `feat/menu-visibilidad-opciones` (actual) salvo que se decida una rama dedicada.

---

## Convenciones de este repo (leer antes de empezar)

- Editar archivos en el host se refleja **vivo** en el container (bind mount). No hace falta rebuild.
- Lint PHP: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/<ruta>`.
- DB: `MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "<sql>"`.
- DBs de tenant con bot: averiguar con `SHOW DATABASES LIKE 'atiende%'` (típicas: `atiende_demo`, `atiende_corp`, `atiende_demo_1`). La DB base `atiende` **no** tiene `bot_config`.
- **No tocar** los bloques legacy Bootstrap 3 (`$_SESSION['x'] == 10`).
- Sanitización en el motor: usar `Connection::escape(...)` en valores interpolados (ya se usa en `BotEngine.php`).

---

## File Structure

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `app/_docker/migrations/2026-06-10-vendedor.php` | Crear | Migración idempotente por tenant: agrega `contactos.vendedor_codigo` + parchea `menu_json` (opción "Soy Vendedor" + menús 105/106) |
| `app/_docker/migrations/2026-06-10-clientes-cuil-dni.php` | Crear | Migración idempotente por tenant: agrega `clientes.cuil` y `clientes.dni` |
| `app/_docker/mariadb/atiende.sql` | Modificar | Esquema fresco: `vendedor_codigo` en `contactos`; `cuil`/`dni` en `clientes` |
| `app/_docker/mariadb/bot_config_seed.sql` | Modificar | Menú default de tenants nuevos: menús 105/106 + opción en 100 |
| `app/modelos/BotEngine.php` | Modificar | Leer vendedor de sesión, routing, acciones `registraVendedor` y `chequearVendedorCliente`, atribución del link |
| `app/modelos/Persona.php` | Modificar | `insertar`/`editar` incluyen `cuil`/`dni` |
| `app/ajax/persona.php` | Modificar | Leer `cuil`/`dni` del POST y pasarlos a ambos call sites |
| `app/vistas/cliente.php` | Modificar | Inputs CUIL/DNI en el form BS5 (`== 1`) |
| `app/vistas/scripts/cliente.js` | Modificar | `limpiar()` y `mostrar()` para CUIL/DNI |

---

# PARTE A — Flujo de vendedor en el bot

## Task A1: Migración — columna `contactos.vendedor_codigo` + parche de `menu_json`

**Files:**
- Create: `app/_docker/migrations/2026-06-10-vendedor.php`

- [ ] **Step 1: Crear el script de migración idempotente**

```php
<?php
// Migración idempotente por tenant: contactos.vendedor_codigo + menú vendedor (105/106 + opción en 100).
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-vendedor.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }

$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }
$m->set_charset('utf8mb4');

// 1) Columna contactos.vendedor_codigo (si la tabla existe y la columna no)
$hasContactos = $m->query("SHOW TABLES LIKE 'contactos'")->num_rows > 0;
if ($hasContactos) {
    $col = $m->query("SHOW COLUMNS FROM `contactos` LIKE 'vendedor_codigo'");
    if ($col->num_rows === 0) {
        $m->query("ALTER TABLE `contactos` ADD COLUMN `vendedor_codigo` VARCHAR(50) NULL DEFAULT NULL");
        echo "[$db] + contactos.vendedor_codigo\n";
    } else {
        echo "[$db] = contactos.vendedor_codigo (ya existe)\n";
    }
} else {
    echo "[$db] sin tabla contactos, salto columna\n";
}

// 2) Parche del menú (si bot_config tiene la fila 1)
$hasBotConfig = $m->query("SHOW TABLES LIKE 'bot_config'")->num_rows > 0;
if ($hasBotConfig) {
    $res = $m->query("SELECT menu_json FROM bot_config WHERE id=1");
    if ($res && $res->num_rows > 0) {
        $menu = json_decode($res->fetch_assoc()['menu_json'], true);
        if (is_array($menu)) {
            $ids = array_column($menu, 'menuId');

            // Opción "Soy Vendedor" en menú 100 (idempotente)
            foreach ($menu as &$entry) {
                if (($entry['menuId'] ?? '') === '100') {
                    $tiene = false;
                    foreach (($entry['menuItem'] ?? []) as $it) {
                        if (($it['menuId'] ?? '') === '105') { $tiene = true; break; }
                    }
                    if (!$tiene) {
                        $entry['menuItem'][] = ["opcionId"=>"5","opcion"=>"Soy Vendedor","menuId"=>"105","guardar"=>"false","area"=>""];
                        echo "[$db] + opción 'Soy Vendedor' en menú 100\n";
                    }
                }
            }
            unset($entry);

            // Detectar el menú destino del link de pedido — varía por tenant:
            // 350 en demo/corp, 300 en el seed default. Se busca por el placeholder <linkPedidos>.
            $linkMenuId = '';
            foreach ($menu as $e) {
                if (strpos($e['consigna'] ?? '', '<linkPedidos>') !== false) { $linkMenuId = (string)$e['menuId']; break; }
            }
            if ($linkMenuId === '') {
                fwrite(STDERR, "[$db] ADVERTENCIA: no se encontró el menú con <linkPedidos>; no se crea el menú 106\n");
            }

            // Menú 105 (idempotente)
            if (!in_array('105', $ids, true)) {
                $menu[] = ["menuId"=>"105","consigna"=>"Ingresá tu *código de vendedor*:","finaliza"=>"false",
                    "menuItem"=>[["opcionId"=>"","opcion"=>"","menuId"=>"106","guardar"=>"false","area"=>"","accion"=>"registraVendedor"]]];
                echo "[$db] + menú 105\n";
            }
            // Menú 106 (idempotente) — su captura apunta al menú del link detectado
            if (!in_array('106', $ids, true) && $linkMenuId !== '') {
                $menu[] = ["menuId"=>"106","consigna"=>"Ingresá el *código del cliente* al que vas a cargar el pedido.\n\n(Escribí *SALIR* para cerrar tu sesión de vendedor.)","finaliza"=>"false",
                    "menuItem"=>[["opcionId"=>"","opcion"=>"","menuId"=>$linkMenuId,"guardar"=>"false","area"=>"","accion"=>"chequearVendedorCliente"]]];
                echo "[$db] + menú 106 (captura → $linkMenuId)\n";
            }

            $json = json_encode($menu, JSON_UNESCAPED_UNICODE);
            $stmt = $m->prepare("UPDATE bot_config SET menu_json=? WHERE id=1");
            $stmt->bind_param('s', $json);
            $stmt->execute();
            $stmt->close();
            echo "[$db] menú actualizado: " . implode(',', array_column($menu, 'menuId')) . "\n";
        }
    } else {
        echo "[$db] bot_config sin fila id=1, salto menú\n";
    }
} else {
    echo "[$db] sin tabla bot_config, salto menú\n";
}
$m->close();
```

- [ ] **Step 2: Ejecutar la migración en `atiende_demo`**

Run:
```
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-vendedor.php atiende_demo
```
Expected: líneas `+ contactos.vendedor_codigo`, `+ opción 'Soy Vendedor'…`, `+ menú 105`, `+ menú 106`, `menú actualizado: …,105,106`.

- [ ] **Step 3: Verificar columna y menú**

Run:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SHOW COLUMNS FROM atiende_demo.contactos LIKE 'vendedor_codigo'; SELECT menu_json LIKE '%registraVendedor%' AND menu_json LIKE '%\"105\"%' AND menu_json LIKE '%\"106\"%' AS ok FROM atiende_demo.bot_config WHERE id=1;"
```
Expected: una fila para la columna; `ok = 1`.

- [ ] **Step 4: Verificar idempotencia (correr de nuevo no duplica)**

Run el mismo comando del Step 2.
Expected: líneas con `=` / `ya existe` y **sin** un segundo "+ opción 'Soy Vendedor'".

Verificar conteo (independiente de posición): `"menuId":"105"` debe aparecer exactamente **2 veces** (la opción del menú 100 que apunta a 105 + la entrada del menú 105):
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SELECT (LENGTH(menu_json)-LENGTH(REPLACE(menu_json,'\"menuId\":\"105\"','')))/LENGTH('\"menuId\":\"105\"') AS veces105 FROM atiende_demo.bot_config WHERE id=1;"
```
Expected: `2` (no 4 tras una segunda corrida).

- [ ] **Step 5: Commit**

```bash
git add app/_docker/migrations/2026-06-10-vendedor.php
git commit -m "feat(bot): migración vendedor — contactos.vendedor_codigo + menús 105/106"
```

---

## Task A2: BotEngine — leer vendedor de sesión + routing inicial

**Files:**
- Modify: `app/modelos/BotEngine.php` (bloque del `SELECT` de estado ~L187 y bloque `if ($menu == '0')` ~L193-205)

- [ ] **Step 1: Agregar `vendedor_codigo` al SELECT de estado**

Reemplazar:
```php
        $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior FROM contactos where telefono LIKE '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row      = mysqli_fetch_assoc($request);
            $anterior = json_decode($row['anterior'], TRUE)['opcion'];
        }
```
por:
```php
        $codigoVendedor = '';
        $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior,vendedor_codigo FROM contactos where telefono LIKE '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row            = mysqli_fetch_assoc($request);
            $anterior       = json_decode($row['anterior'], TRUE)['opcion'];
            $codigoVendedor = $row['vendedor_codigo'] ?? '';
        }
```

- [ ] **Step 2: Rutear a 106 si hay vendedor de sesión**

Dentro de `if ($menu == '0') { … }`, reemplazar:
```php
            if (strlen($codigoCliente) == 0) {
                $menu = $this->menuJson[0]['menuIdB'];
            } else {
                $menu = '200'; // cliente identificado → ir directo al menú principal
            }
```
por:
```php
            if (strlen($codigoVendedor) > 0) {
                $menu = '106'; // vendedor reconocido → pedir código de cliente
            } elseif (strlen($codigoCliente) == 0) {
                $menu = $this->menuJson[0]['menuIdB'];
            } else {
                $menu = '200'; // cliente identificado → ir directo al menú principal
            }
```

- [ ] **Step 3: Lint**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/modelos/BotEngine.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add app/modelos/BotEngine.php
git commit -m "feat(bot): leer contactos.vendedor_codigo y rutear vendedor reconocido a menú 106"
```

---

## Task A3: BotEngine — acción `registraVendedor`

**Files:**
- Modify: `app/modelos/BotEngine.php` (rama de acciones, justo antes de `if ($menuItem[$j]['accion'] == 'registraNumero') {`)

- [ ] **Step 1: Insertar el bloque de la acción**

Antes de la línea `if ($menuItem[$j]['accion'] == 'registraNumero') {`, insertar:
```php
                                    if ($menuItem[$j]['accion'] === 'registraVendedor') {
                                        $req = Connection::runQuery("SELECT codigo FROM vendedores WHERE codigo = '" . Connection::escape($mensaje) . "'");
                                        if ($req && mysqli_num_rows($req) > 0) {
                                            $rowV = mysqli_fetch_assoc($req);
                                            Connection::runQuery("UPDATE `contactos` SET `vendedor_codigo`= '" . Connection::escape($rowV['codigo']) . "', `mensaje`='' where id like '" . $user . "'");
                                        } else {
                                            $this->client->sendText($user, 'El código de vendedor no es válido. Volvé a intentarlo escribiendo *Hola* nuevamente.');
                                            Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0', `vendedor_codigo`=NULL where id like '" . $user . "'");
                                            return;
                                        }
                                    }
```

- [ ] **Step 2: Lint**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/modelos/BotEngine.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add app/modelos/BotEngine.php
git commit -m "feat(bot): acción registraVendedor (valida código y guarda vendedor en sesión)"
```

---

## Task A4: BotEngine — reescribir `chequearVendedorCliente` (sesión + reintento + Salir)

**Files:**
- Modify: `app/modelos/BotEngine.php` (bloque `if ($menuItem[$j]['accion'] === 'chequearVendedorCliente') { … }`, ~L401-422)

- [ ] **Step 1: Reemplazar el bloque completo**

Reemplazar todo el bloque actual de `chequearVendedorCliente` por:
```php
                                    if ($menuItem[$j]['accion'] === 'chequearVendedorCliente') {
                                        // Vendedor de sesión (guardado por registraVendedor en contactos.vendedor_codigo)
                                        $codVend = '';
                                        $reqV = Connection::runQuery("SELECT vendedor_codigo FROM contactos WHERE id = '" . $user . "'");
                                        if ($reqV && mysqli_num_rows($reqV) > 0) {
                                            $codVend = mysqli_fetch_assoc($reqV)['vendedor_codigo'] ?? '';
                                        }

                                        // "Salir": cierra la sesión de vendedor.
                                        if (strcasecmp(trim($mensaje), 'salir') === 0) {
                                            Connection::runQuery("UPDATE `contactos` SET `vendedor_codigo`=NULL, `mensaje`='', `anterior`='', `esperaRespuesta`=0, `menu`='0' where id like '" . $user . "'");
                                            $this->client->sendText($user, 'Cerraste tu sesión de vendedor. ¡Hasta pronto!');
                                            return;
                                        }

                                        // Validar que el código de cliente exista y sea de este vendedor.
                                        $reqC = Connection::runQuery("SELECT razonSocial, codigo FROM clientes WHERE codigo = '" . Connection::escape($mensaje) . "' AND vendedor = '" . Connection::escape($codVend) . "'");
                                        if ($reqC && mysqli_num_rows($reqC) > 0) {
                                            $rowCliente = mysqli_fetch_assoc($reqC);
                                            Connection::runQuery("UPDATE `vendedores` SET `atencion`= '" . Connection::escape($rowCliente['codigo']) . "' where codigo like '" . Connection::escape($codVend) . "'");
                                            Connection::runQuery("UPDATE `contactos` SET `mensaje`='' where id like '" . $user . "'");
                                            $this->client->sendText($user, 'Cliente: ' . $rowCliente['razonSocial']);
                                        } else {
                                            // Reintento: NO se resetea el estado → el próximo mensaje es otro intento.
                                            $this->client->sendText($user, 'Codigo de cliente ingresado incorrecto intente nuevamente');
                                            return;
                                        }
                                    }
```

- [ ] **Step 2: Lint**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/modelos/BotEngine.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add app/modelos/BotEngine.php
git commit -m "feat(bot): chequearVendedorCliente por sesión, con reintento y opción Salir"
```

---

## Task A5: BotEngine — atribución del link de pedido al vendedor de sesión

**Files:**
- Modify: `app/modelos/BotEngine.php` (bloque `if (!$esPromo && strlen($notiPedido) > 0) { … }`, ~L269-289)

- [ ] **Step 1: Anteponer la rama por sesión, preservando el fallback por teléfono**

Reemplazar:
```php
                    if (!$esPromo && strlen($notiPedido) > 0) {
                        $vendedorR   = '';
                        $requestVend = Connection::runQuery("SELECT atencion  FROM vendedores where telefono= '" . $user . "'");
                        if (mysqli_num_rows($requestVend) > 0) {
                            $rowVendedor = mysqli_fetch_assoc($requestVend);
                            if ($rowVendedor['atencion'] !== null) {
                                Connection::runQuery("UPDATE `link_pedidos` SET `clienteId`= '" . $rowVendedor['atencion'] . "'  where id = '" . $notiPedido . "'");
                                $requestVendedor = Connection::runQuery("SELECT codigo  FROM vendedores where atencion= '" . $rowVendedor['atencion'] . "'");
                                if (mysqli_num_rows($requestVendedor) > 0) {
                                    $rowVendedor = mysqli_fetch_assoc($requestVendedor);
                                    $vendedorR   = $rowVendedor['codigo'];
                                    Connection::runQuery("UPDATE `vendedores` SET `atencion`= ''  where telefono= '" . $user . "'");
                                }
                            }
                        }
                        $pedidoUrl = tenantUrl($this->empresa, '/pedidos/' . $notiPedido . ($vendedorR !== '' ? '/' . $vendedorR : ''));
```
por:
```php
                    if (!$esPromo && strlen($notiPedido) > 0) {
                        $vendedorR   = '';
                        if (strlen($codigoVendedor) > 0) {
                            // Vendedor identificado por código (sesión en contactos.vendedor_codigo)
                            $reqAt = Connection::runQuery("SELECT atencion FROM vendedores WHERE codigo = '" . Connection::escape($codigoVendedor) . "'");
                            if ($reqAt && mysqli_num_rows($reqAt) > 0) {
                                $at = mysqli_fetch_assoc($reqAt)['atencion'];
                                if ($at !== null && $at !== '') {
                                    Connection::runQuery("UPDATE `link_pedidos` SET `clienteId`= '" . Connection::escape($at) . "' where id = '" . $notiPedido . "'");
                                    $vendedorR = $codigoVendedor;
                                    Connection::runQuery("UPDATE `vendedores` SET `atencion`= '' where codigo = '" . Connection::escape($codigoVendedor) . "'");
                                }
                            }
                        } else {
                            $requestVend = Connection::runQuery("SELECT atencion  FROM vendedores where telefono= '" . $user . "'");
                            if (mysqli_num_rows($requestVend) > 0) {
                                $rowVendedor = mysqli_fetch_assoc($requestVend);
                                if ($rowVendedor['atencion'] !== null) {
                                    Connection::runQuery("UPDATE `link_pedidos` SET `clienteId`= '" . $rowVendedor['atencion'] . "'  where id = '" . $notiPedido . "'");
                                    $requestVendedor = Connection::runQuery("SELECT codigo  FROM vendedores where atencion= '" . $rowVendedor['atencion'] . "'");
                                    if (mysqli_num_rows($requestVendedor) > 0) {
                                        $rowVendedor = mysqli_fetch_assoc($requestVendedor);
                                        $vendedorR   = $rowVendedor['codigo'];
                                        Connection::runQuery("UPDATE `vendedores` SET `atencion`= ''  where telefono= '" . $user . "'");
                                    }
                                }
                            }
                        }
                        $pedidoUrl = tenantUrl($this->empresa, '/pedidos/' . $notiPedido . ($vendedorR !== '' ? '/' . $vendedorR : ''));
```

- [ ] **Step 2: Lint**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/modelos/BotEngine.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add app/modelos/BotEngine.php
git commit -m "feat(bot): atribuir el link de pedido al vendedor de sesión (fallback por teléfono intacto)"
```

---

## Task A6: Esquema fresco + seed para tenants nuevos

**Files:**
- Modify: `app/_docker/mariadb/atiende.sql` (CREATE TABLE `contactos`)
- Modify: `app/_docker/mariadb/bot_config_seed.sql` (menu_json default)

- [ ] **Step 1: Agregar la columna al esquema fresco**

En el `CREATE TABLE \`contactos\``, después de `\`mensaje\` longtext … NULL,` agregar:
```sql
  `vendedor_codigo` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
```

- [ ] **Step 2: Regenerar el `menu_json` del seed sobre una DB descartable**

> **No** copiar el menú de `demo`: en demo el link de pedido es el menú `350`, pero el seed default usa `300`. La migración detecta el menú correcto por `<linkPedidos>`, así que se la corre sobre una DB sembrada con el **seed actual** (donde detectará `300` y cableará `106 → 300`).

Run:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS _seedcheck; CREATE DATABASE _seedcheck;"
MSYS_NO_PATHCONV=1 docker exec -i mysql8 mysql -uroot -proot _seedcheck < app/_docker/mariadb/bot_config_seed.sql
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-vendedor.php _seedcheck
```
Expected: `+ opción 'Soy Vendedor'`, `+ menú 105`, `+ menú 106 (captura → 300)`.

- [ ] **Step 3: Emitir el literal SQL escapado y reemplazarlo en el seed**

Generar el literal del `menu_json` con escape MySQL correcto (`\` → `\\`, `'` → `\'`, así los `\n` de las consignas sobreviven):
```
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php -r '$m=new mysqli("mysql8","root","root","_seedcheck"); $j=$m->query("SELECT menu_json FROM bot_config WHERE id=1")->fetch_assoc()["menu_json"]; echo addslashes($j);'
```
Reemplazar el contenido entre comillas del `INSERT … SELECT 1, '<aquí>'` en `bot_config_seed.sql` por esa salida. Verificar que el literal contenga `\"105\"`, `\"106\"`, `registraVendedor` y `chequearVendedorCliente`.

- [ ] **Step 4: Validar el seed editado contra una DB limpia y limpiar**

Run:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE IF EXISTS _seedcheck2; CREATE DATABASE _seedcheck2;"
MSYS_NO_PATHCONV=1 docker exec -i mysql8 mysql -uroot -proot _seedcheck2 < app/_docker/mariadb/bot_config_seed.sql
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SELECT JSON_VALID(menu_json), menu_json LIKE '%registraVendedor%', menu_json LIKE '%\"106\"%' FROM _seedcheck2.bot_config WHERE id=1;"
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "DROP DATABASE _seedcheck; DROP DATABASE _seedcheck2;"
```
Expected: `1   1   1` (JSON válido, contiene la acción nueva y el menú 106).

- [ ] **Step 5: Commit**

```bash
git add app/_docker/mariadb/atiende.sql app/_docker/mariadb/bot_config_seed.sql
git commit -m "feat(bot): esquema fresco y seed con vendedor_codigo + menús 105/106"
```

---

## Task A7: Datos de prueba en demo + E2E manual

**Files:** ninguno (datos + verificación).

- [ ] **Step 1: Sembrar un vendedor y clientes asociados en `atiende_demo`**

Run:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -e "
INSERT INTO atiende_demo.vendedores (codigo,nombre,telefono,supervisor) VALUES ('V001','Vendedor Test','5493760000000','')
  ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
UPDATE atiende_demo.clientes SET vendedor='V001' WHERE codigo IN (SELECT codigo FROM (SELECT codigo FROM atiende_demo.clientes LIMIT 2) t);
SELECT codigo,razonSocial,vendedor FROM atiende_demo.clientes WHERE vendedor='V001';"
```
Expected: ≥1 cliente listado con `vendedor=V001`. Anotar un `codigo` válido y tener a mano uno inválido.

- [ ] **Step 2: E2E por WhatsApp (número de prueba aprobado en Meta) contra `demo`**

Checklist (seguir `docs/runbook-bot-no-carga-menu.md` ante fallas de carga):
- [ ] `hola` → aparece el menú 100 con la opción **"Soy Vendedor"** (renumerada por `proyectarMenu`, "Salir" última).
- [ ] Elegir "Soy Vendedor" → pide *"Ingresá tu código de vendedor:"*.
- [ ] Código de vendedor **inválido** → *"El código de vendedor no es válido…"* y vuelve al inicio.
- [ ] Código de vendedor **válido** (`V001`) → pide *"Ingresá el código del cliente…"*.
- [ ] Código de cliente **incorrecto** → *"Codigo de cliente ingresado incorrecto intente nuevamente"* y **permite reintentar** (no se reinicia).
- [ ] Código de cliente **correcto** → *"Cliente: <razón social>"* + link `/pedidos/{id}/V001`.
- [ ] Escribir `hola` de nuevo → el bot **reconoce** al vendedor y pide directamente el código de cliente.
- [ ] Escribir `SALIR` en ese paso → *"Cerraste tu sesión de vendedor…"*; tras eso `hola` vuelve a mostrar el menú 100 normal.

- [ ] **Step 3: Aplicar la migración a los demás tenants con bot**

Run (descubrir y migrar cada uno):
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SHOW DATABASES LIKE 'atiende%';"
```
Para cada DB de tenant con bot (p.ej. `atiende_corp`, `atiende_demo_1`):
```
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-vendedor.php <db>
```
Expected: sin errores; menús 105/106 agregados donde haya `bot_config`.

---

# PARTE B — CUIL/DNI en clientes (independiente)

## Task B1: Migración + esquema — `clientes.cuil` y `clientes.dni`

**Files:**
- Create: `app/_docker/migrations/2026-06-10-clientes-cuil-dni.php`
- Modify: `app/_docker/mariadb/atiende.sql` (CREATE TABLE `clientes`)

- [ ] **Step 1: Crear el script de migración idempotente**

```php
<?php
// Idempotente por tenant: agrega clientes.cuil y clientes.dni.
// Uso: docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-clientes-cuil-dni.php <db>
$db = $argv[1] ?? '';
if ($db === '') { fwrite(STDERR, "Falta el nombre de la DB\n"); exit(1); }
$m = new mysqli('mysql8', 'root', 'root', $db);
if ($m->connect_errno) { fwrite(STDERR, "Conexión: {$m->connect_error}\n"); exit(1); }
if ($m->query("SHOW TABLES LIKE 'clientes'")->num_rows === 0) { echo "[$db] sin tabla clientes\n"; exit(0); }
foreach (['cuil','dni'] as $colName) {
    $col = $m->query("SHOW COLUMNS FROM `clientes` LIKE '$colName'");
    if ($col->num_rows === 0) {
        $m->query("ALTER TABLE `clientes` ADD COLUMN `$colName` VARCHAR(20) NULL DEFAULT NULL");
        echo "[$db] + clientes.$colName\n";
    } else {
        echo "[$db] = clientes.$colName (ya existe)\n";
    }
}
$m->close();
```

- [ ] **Step 2: Ejecutar en `atiende_demo` y verificar**

Run:
```
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-clientes-cuil-dni.php atiende_demo
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SHOW COLUMNS FROM atiende_demo.clientes WHERE Field IN ('cuil','dni');"
```
Expected: dos filas (`cuil`, `dni`).

- [ ] **Step 3: Agregar las columnas al esquema fresco**

En el `CREATE TABLE \`clientes\`` de `atiende.sql`, agregar (p.ej. después de `telefono`):
```sql
  `cuil` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
  `dni`  varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NULL DEFAULT NULL,
```

- [ ] **Step 4: Commit**

```bash
git add app/_docker/migrations/2026-06-10-clientes-cuil-dni.php app/_docker/mariadb/atiende.sql
git commit -m "feat(clientes): migración y esquema para cuil/dni"
```

---

## Task B2: Modelo `Persona` — `insertar`/`editar` incluyen cuil/dni

**Files:**
- Modify: `app/modelos/Persona.php`

- [ ] **Step 1: `editar` — agregar parámetros y SET**

Cambiar la firma `editar($idpersona,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono)` a:
```php
	public function editar($idpersona,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono,$cuil,$dni){
```
y agregar `cuil='$cuil', dni='$dni'` al `SET` del `UPDATE` (junto a los demás campos).

- [ ] **Step 2: `insertar` — agregar parámetros y columnas**

Cambiar la firma `insertar($codigo,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono,$deposito,$latitud,$longitud)` a:
```php
	public function insertar($codigo,$vendedor,$nombre,$direccion,$localidad,$ramo,$zona,$lista,$telefono,$deposito,$latitud,$longitud,$cuil,$dni){
```
y agregar `cuil`, `dni` a la lista de columnas y `'$cuil'`, `'$dni'` a `VALUES` del `INSERT`.

> `mostrar()` usa `SELECT *`, así que ya devuelve `cuil`/`dni` sin cambios.

- [ ] **Step 3: Lint**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/modelos/Persona.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add app/modelos/Persona.php
git commit -m "feat(clientes): Persona.insertar/editar persisten cuil/dni"
```

---

## Task B3: `ajax/persona.php` — leer y pasar cuil/dni

**Files:**
- Modify: `app/ajax/persona.php` (lectura de POST + call sites L39 `insertar` y L43 `editar`)

- [ ] **Step 1: Leer cuil/dni del POST**

Junto a las demás lecturas (`$deposito=isset($_POST["deposito"])…`), agregar:
```php
$cuil=isset($_POST["cuil"])? limpiarCadena($_POST["cuil"]):"";
$dni=isset($_POST["dni"])? limpiarCadena($_POST["dni"]):"";
```

- [ ] **Step 2: Pasar a ambos call sites**

- `insertar(...)`: agregar `,$cuil,$dni` **al final** de los argumentos.
- `editar(...)`: agregar `,$cuil,$dni` **al final** de los argumentos.

- [ ] **Step 3: Lint**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/ajax/persona.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add app/ajax/persona.php
git commit -m "feat(clientes): ajax/persona pasa cuil/dni a insertar/editar"
```

---

## Task B4: Vista `cliente.php` + `cliente.js` — inputs y relleno

**Files:**
- Modify: `app/vistas/cliente.php` (form BS5, bloque `$_SESSION['ventas'] == 1`)
- Modify: `app/vistas/scripts/cliente.js` (`limpiar()` y `mostrar()`)

- [ ] **Step 1: Agregar inputs CUIL y DNI al form (BS5)**

En `app/vistas/cliente.php`, dentro del `<form id="formulario">` del bloque `== 1` (después del row de Longitud, antes del botón Guardar ~L237), agregar:
```html
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">CUIL</label>
                                                <input class="form-control" type="text" name="cuil" id="cuil" maxlength="20" placeholder="CUIL (opcional)">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 position-relative">
                                                <label for="" class="form-label">DNI</label>
                                                <input class="form-control" type="text" name="dni" id="dni" maxlength="20" placeholder="DNI (opcional)">
                                            </div>
                                        </div>
                                    </div>
```
> No tocar el bloque legacy `$_SESSION['ventas'] == 10`.

- [ ] **Step 2: `cliente.js` — limpiar()**

En `limpiar()`, junto a los `$("#deposito").val("")`, agregar:
```js
	$("#cuil").val("");
	$("#dni").val("");
```

- [ ] **Step 3: `cliente.js` — mostrar()**

En `mostrar()`, junto a `$("#longitud").val(data.longitud)`, agregar:
```js
			$("#cuil").val(data.cuil);
			$("#dni").val(data.dni);
```
> `guardaryeditar` usa `new FormData($("#formulario")[0])`, así que los inputs `name="cuil"`/`name="dni"` se envían solos — no requiere cambios ahí.

- [ ] **Step 4: Lint de la vista**

Run: `MSYS_NO_PATHCONV=1 docker exec atiende-app php -l /var/www/atiende/vistas/cliente.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Verificación E2E (ABM)**

- [ ] Abrir `http://demo.atiende.localhost:81/vistas/cliente.php`, editar un cliente, cargar CUIL y DNI, Guardar.
- [ ] Verificar persistencia:
```
MSYS_NO_PATHCONV=1 docker exec mysql8 mysql -uroot -proot -N -e "SELECT codigo,cuil,dni FROM atiende_demo.clientes WHERE cuil<>'' OR dni<>'' LIMIT 5;"
```
Expected: la fila editada muestra los valores.
- [ ] Reabrir el mismo cliente → el form muestra CUIL/DNI rellenos.
- [ ] Guardar un cliente **sin** CUIL/DNI → no rompe (quedan vacíos/NULL).

- [ ] **Step 6: Commit**

```bash
git add app/vistas/cliente.php app/vistas/scripts/cliente.js
git commit -m "feat(clientes): inputs CUIL/DNI en el form de edición"
```

- [ ] **Step 7: Aplicar la migración de cuil/dni a los demás tenants**

Para cada DB de tenant (`SHOW DATABASES LIKE 'atiende%'`):
```
MSYS_NO_PATHCONV=1 docker exec -i atiende-app php /var/www/atiende/_docker/migrations/2026-06-10-clientes-cuil-dni.php <db>
```

---

## Cierre

- [ ] Actualizar `app/CLAUDE.md` (sección bot + gotchas) con: el flujo de vendedor (menús 105/106, `registraVendedor`, `chequearVendedorCliente` por sesión + reintento + Salir), `contactos.vendedor_codigo` como sesión que **no** pisa `vendedores.telefono`, y los campos `clientes.cuil/dni`. Commit.
- [ ] Revisar el diff completo con `superpowers:requesting-code-review` antes de mergear.

## Notas de riesgo / decisiones

- **Sin test suite** (CLAUDE.md): la verificación es `php -l` + roundtrips de DB + E2E manual. No se introducen unit tests porque el motor es DB-coupled; `proyectarMenu` **no** se modifica, así que su test espejo queda intacto.
- **`vendedores.telefono` no se toca** — sigue siendo el contacto del vendedor para notificaciones; la sesión vive en `contactos.vendedor_codigo`.
- **Reintento sin tope** (decisión de producto); se sale con `SALIR` o reiniciando con `hola`.
- **Meta test-mode (#131030):** el número de prueba debe estar aprobado en Meta y cargado sin el `9` móvil.
