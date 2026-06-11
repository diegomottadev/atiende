# Normalización de teléfono por país (multi-tenant)

**Fecha:** 2026-06-11
**Estado:** Aprobado (diseño)
**Rama:** `feat/menu-visibilidad-opciones` (o rama nueva a definir)

## Resumen

Hoy la normalización de números a formato WhatsApp (`wa_id`) **asume Argentina**: antepone `549` a cualquier número local. Esto rompe si un tenant opera en otro país. Esta feature hace que la normalización dependa del **país del tenant** (cada negocio que usa Atiende opera en un país), soportando un conjunto acotado de países de LatAm mediante un mapa mantenido a mano (sin librerías externas).

**Decisión de alcance (acordada):** país **por tenant** (no por comprador final — los compradores ya llegan con `wa_id` internacional desde Meta). Conjunto inicial: AR, BR, MX, UY, CL, PY, CO, PE. Extensible agregando una línea al mapa.

## Contexto del sistema (estado actual)

- El `wa_id` entrante lo entrega Meta en formato internacional para **cualquier** país; `ws/webhook.php` lo deja en solo-dígitos (`$user`). No requiere normalización.
- El supuesto argentino (`549`) está **duplicado** en 3 lugares, todos normalizando números **tipeados por el admin** (los números propios del tenant):
  - `ajax/vendedor.php` (`guardaryeditar`): `preg_replace('/\D/','')` → `ltrim('0')` → `if (!startsWith '54') '549'.$n`.
  - `ajax/configuracion.php` (`saveAdminCopia`): misma lógica.
  - `pedidos/finaliza.php`: `(startsWith '54') ? $d : '549'.ltrim($d,'0')`.
- `config/WhatsAppClient.php::normalizePhone` (envío): convierte `549…→54…` (saca el `9` móvil AR) y deja el resto de países **tal cual**. Detecta AR por el prefijo `549`, así que ya es seguro para otros países.
- **Identidad de empresa:** fuente de verdad en `pedidos_platform.tenants` (`getEmpresa` lee de ahí; `guardarEmpresa` escribe ahí **y** cachea `telefono`/`logo` en `bot_config` local del tenant). Pestaña **Empresa** de `configuracion.php`.
- `pedidos_platform.tenants` NO tiene columna `pais`. `bot_config` tampoco.

### El insight que simplifica el mapa

Solo **Argentina** necesita lógica especial: el `9` móvil **no** forma parte de cómo un argentino escribe su número local (escribe `11 1234 5678`), es un agregado de WhatsApp. En los demás países de la lista, el admin escribe su móvil local **ya con** el dígito que corresponde (Brasil incluye su 9º dígito, México sus 10 dígitos), así que alcanza con **anteponer el código de país**. El "quirk" de Brasil/México solo afecta números *legacy* (caso de borde, fuera de alcance).

Por eso el mapa por país se reduce a: **`código de país` + un flag `movil9` que solo usa AR**.

## Cambios de datos

```sql
-- En cada DB de tenant (bot_config = cache local para la normalización):
ALTER TABLE `bot_config` ADD COLUMN `pais` VARCHAR(2) NOT NULL DEFAULT 'AR';

-- En el registro central (fuente de verdad, visible al superadmin):
ALTER TABLE `pedidos_platform`.`tenants` ADD COLUMN `pais` VARCHAR(2) NOT NULL DEFAULT 'AR';
```
- Migración idempotente por tenant (patrón `_docker/migrations/`), + columnas en `_docker/mariadb/atiende.sql` (`bot_config`… ver nota) y `bot_config_seed.sql`.
- `tenants.pais` se aplica una sola vez (DB central, no por tenant).
- **Default `'AR'` y backfill `'AR'`** → el comportamiento actual de los tenants existentes no cambia.

> Nota: el `CREATE TABLE bot_config` "fresco" vive en `bot_config_seed.sql` (no en `atiende.sql`, que no tiene `bot_config`). Agregar `pais` ahí.

## Componente nuevo: `config/Telefono.php` (lógica pura)

Clase **sin dependencias** (no toca DB) → unit-testeable directamente.

```php
class Telefono {
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

    /** Normaliza un número tipeado a formato wa_id según el país del tenant. */
    public static function normalizar($numero, $pais = 'AR'): string {
        $d = preg_replace('/\D/', '', (string) $numero);
        $d = ltrim($d, '0');                                 // troncal local (0…) primero → evita prefijo duplicado
        if ($d === '') return '';
        $p  = self::PAISES[$pais] ?? self::PAISES['AR'];      // país desconocido → AR (retrocompat)
        $cc = $p['cc'];
        if (strncmp($d, $cc, strlen($cc)) === 0) return $d;  // ya trae código de país → tal cual
        return !empty($p['movil9']) ? $cc . '9' . $d : $cc . $d;
    }
}
```

- Vacío → vacío (los teléfonos son opcionales).
- País nulo/desconocido → AR (preserva el comportamiento actual).
- Para AR reproduce **exactamente** la lógica de hoy (`549` + local; respeta números que ya empiezan con `54`).

## Refactor (reemplazar el `549` hardcodeado por el helper)

Cada punto lee el `pais` del tenant desde `bot_config` (DB local ya conectada) y llama al helper:

- `ajax/vendedor.php`: `require_once` del helper; `$pais = SELECT pais FROM bot_config LIMIT 1` (fallback `'AR'`); `$telefono = Telefono::normalizar($telefono, $pais)`.
- `ajax/configuracion.php` (`saveAdminCopia`): igual, sobre `$tel`.
- `pedidos/finaliza.php`: igual, sobre el `telefono` del negocio.
- `config/WhatsAppClient.php::normalizePhone`: **sin cambios funcionales** (ya es AR-only por el prefijo `549`); se documenta. (Opcional futuro: hacerlo país-aware leyendo `tenants.pais`, fuera de alcance.)

## UI

- Dropdown **País** en la pestaña **Empresa** de `configuracion.php`, con las opciones del mapa (AR por default).
- `getEmpresa`: agregar `pais` al `SELECT … FROM tenants`; devolverlo en el JSON. **Agregar `'pais' => 'AR'` al struct de fallback `$emp`** (el que se usa si la consulta PDO falla), para que la respuesta JSON siempre traiga `pais`.
- `guardarEmpresa`: agregar `pais` al `UPDATE tenants` (fuente de verdad) **y** al `UPDATE bot_config` (cache local) — mismo patrón "ambos (sync)" que ya usa `telefono`. **Ojo: el `UPDATE bot_config` tiene DOS ramas** (con logo / sin logo); `pais` debe agregarse en **ambas** (hoy las dos solo bindean `telefono`/`logo`).

## Componentes y límites

| Unidad | Qué hace | Depende de |
|---|---|---|
| `config/Telefono.php` | Normaliza número→`wa_id` según país (lógica pura) | nada |
| `bot_config.pais` / `tenants.pais` | País del tenant (cache local / fuente de verdad) | migración |
| Empresa tab (`getEmpresa`/`guardarEmpresa`) | Setea/lee el país | `tenants`, `bot_config` |
| Refactor de los 3 puntos de normalización | Usan el helper con el país del tenant | `config/Telefono.php`, `bot_config.pais` |

## Beneficio directo

La verificación de vendedor (`vendedores.telefono = $user`, agregada en la feature anterior) pasa a funcionar para cualquier país soportado: el `telefono` se guarda con el **mismo** helper que produce el formato del `wa_id` entrante, así que comparan iguales.

## Manejo de errores / casos borde

- **País desconocido o `bot_config.pais` ausente** → AR (retrocompat). Nunca falla por país inválido.
- **Número ya con código de país** → se respeta (no se duplica el prefijo).
- **Número vacío** → vacío (campo opcional).
- **AR sin `9` ya cargado** (`5411…`) → se respeta tal cual (igual que hoy; no se fuerza el `9` si ya trae `54`).
- **Migración idempotente** (MySQL 8 sin `ADD COLUMN IF NOT EXISTS`): chequear `information_schema`/`SHOW COLUMNS` antes del `ALTER`.
- **Legacy BR/MX** (números viejos sin/con el dígito de móvil que cambió Meta): fuera de alcance; documentado.

## Testing

- **Unit test** `tests/TelefonoNormalizarTest.php`: requiere `config/Telefono.php` directamente (pura, sin DB) y cubre: AR local→`549…`, AR ya internacional→tal cual, AR con `0` troncal (`0376…`)→`549376…`, **AR zero-padded internacional (`0549…`)→`549…` (no duplica prefijo)**, BR/MX/UY local→`cc+local`, UY con `0` troncal (`099…`)→`598 99…`, número con `+`/espacios, vacío→vacío, país desconocido→AR.
- `php -l` sobre los archivos PHP tocados (dentro de `atiende-app`).
- Prueba manual: setear país en la pestaña Empresa; guardar un vendedor con número local y verificar que queda en el formato del país; E2E del vendedor sigue andando en AR.

## Fuera de alcance (YAGNI)

- Librería de teléfonos (libphonenumber).
- Quirks de números legacy de Brasil/México.
- Normalización por comprador final (ya llegan internacionales).
- Hacer `WhatsAppClient::normalizePhone` país-aware (hoy ya es seguro para no-AR).
- Validación de longitud/validez real del número por país.
