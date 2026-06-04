---
name: frontend-patterns
description: Use when working on any vista, DataTables, SweetAlert2, select2, Bootstrap 5 components, or forms. Covers the UI conventions enforced across all vistas/ in this project.
---

# Patrones Frontend — Atiende

## Card pattern — obligatorio en toda vista CRUD

```html
<div class="card sombra-panel" style="border-top:3px solid #727cf5;">
    <!-- Solo si la página tiene filtros: -->
    <div class="card-body pb-2">
        <div class="row g-2 align-items-end"> ...filtros... </div>
    </div>
    <hr class="my-0">
    <!-- Tabla siempre primero si no hay filtros — no dejar card-body vacío antes -->
    <div class="card-body p-0">
        <div class="table-responsive" id="listadoregistros">
            <table id="tbllistado">...</table>
        </div>
    </div>
    <div class="card-body px-3 pb-3" id="formularioregistros">
        ...formulario...
    </div>
</div>
```

CSS de cabecera de tabla (inline en cada archivo):
```css
#tbllistado thead th {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
}
```

Botón de exportar **fuera** del card, en bloque `row mb-2 mt-n4` encima de él.

## DataTables

- Recargar sin resetear página: `.ajax.reload(null, false)`
- Stat chips: `.text()` — nunca `.val()`
- Antes de `.empty()` sobre un trigger que tiene tooltip activo:
```javascript
let t = bootstrap.Tooltip.getInstance(el);
if (t) { t.hide(); t.dispose(); }
container.empty();
// Re-inicializar tooltips en drawCallback
```

## SweetAlert2

Spinner durante llamada async:
```javascript
Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });
$.post(url, data, function(res) {
    Swal.fire({ icon: res.ok ? 'success' : 'error', title: res.ok ? 'Listo' : res.error });
});
```

## select2

```css
/* Single */
.select2-container .select2-selection--single { height: 31px; line-height: 29px; }
/* Multiple — evitar crecimiento vertical */
.select2-container .select2-selection--multiple { max-height: 31px; overflow: hidden; }
```

## Tooltips Bootstrap

Siempre inicializar con `trigger: 'hover'`:
```javascript
$('[data-bs-toggle="tooltip"]').tooltip({ trigger: 'hover' });
```
El default `'hover focus'` deja tooltips pegados después de un click — no usarlo.

## Botones y labels

- Acción principal: **"Nuevo"** (no "Agregar", no "Crear")
- Excepción única: "Agregar Artículos" en `ingreso.php`
- Títulos de sección: `<h6 class="text-muted mb-2" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">`

## Excel import — upload-zone component

```html
<div class="upload-zone" id="dropZone">
    <span id="fileName">Arrastrá o seleccioná un archivo .xlsx</span>
    <input type="file" id="fileInput" accept=".xlsx,.xls" style="display:none">
    <button type="button" id="clearBtn" style="display:none">✕</button>
</div>
<button id="submitBtn" disabled>Importar</button>
```
```javascript
$('#fileInput').on('change', function() {
    $('#fileName').text(this.files[0]?.name || '');
    $('#submitBtn').prop('disabled', !this.files[0]);
    $('#clearBtn').toggle(!!this.files[0]);
});
function clearUpload() {
    $('#fileInput').val(''); $('#fileName').text('...'); $('#submitBtn').prop('disabled', true); $('#clearBtn').hide();
}
```
Referencias: `cliente.php`, `articulo.php`, `vendedor.php`, `repartidores.php`.

## Formularios — prevenir doble envío

```javascript
$('#submitBtn').prop('disabled', true);
$.post(url, data, function(res) {
    $('#submitBtn').prop('disabled', false);
    Swal.fire({ icon: res.ok ? 'success' : 'error', title: res.ok ? 'Guardado' : res.error });
});
```

## URLs del tenant

Usar siempre `globalUrl` (= `tenantUrl()` del PHP) para construir URLs absolutas:
```javascript
var url = globalUrl + '/ajax/pedidos.php';
```
Nunca hardcodear dominios en JS.

## Bootstrap 3 legacy — nunca tocar

Bloques `if ($_SESSION['x'] == 10)` son Bootstrap 3 legacy. Coexisten con los bloques BS5 `== 1`. No modificar ni eliminar.
