<?php
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
} else {
    require 'headerv1.php';
    ?>
    <!-- page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item" style="margin-top:-0.7em">
                            <a href="javascript:void(0);"><img src="../public/img/logo30x30.png" class="icono-ruta-ClubPedido" alt=""> Atiende</a>
                        </li>
                        <li class="breadcrumb-item active">Configuración del Bot</li>
                    </ol>
                </div>
                <div class="float-start mt-3"><h4 class="page-title"><i class="mdi mdi-robot me-1"></i> Configuración del Bot</h4></div>
            </div>
        </div>
    </div>

    <!-- tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card sombra-panel">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-bordered mb-3" id="cfgTabs">
                        <li class="nav-item">
                            <a href="#tab-menu" data-bs-toggle="tab" class="nav-link active">
                                <i class="mdi mdi-format-list-bulleted me-1"></i> Menú Principal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-reclamos" data-bs-toggle="tab" class="nav-link">
                                <i class="mdi mdi-alert-circle-outline me-1"></i> Motivos de Reclamo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-consultas" data-bs-toggle="tab" class="nav-link">
                                <i class="mdi mdi-comment-question-outline me-1"></i> Motivos de Consulta
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-areas" data-bs-toggle="tab" class="nav-link">
                                <i class="mdi mdi-account-group-outline me-1"></i> Áreas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-token" data-bs-toggle="tab" class="nav-link">
                                <i class="mdi mdi-key-outline me-1"></i> Token WhatsApp
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- ===== MENÚ PRINCIPAL ===== -->
                        <div class="tab-pane show active" id="tab-menu">
                            <p class="text-muted mb-3">Editá el texto de cada opción del menú principal del bot. Los números de opción no cambian.</p>
                            <div id="menuPrincipalList"></div>
                        </div>

                        <!-- ===== MOTIVOS RECLAMO ===== -->
                        <div class="tab-pane" id="tab-reclamos">
                            <div class="text-end mb-2">
                                <button class="btn btn-success btn-sm rounded-pill" onclick="abrirModal('reclamos', 0)">
                                    <i class="mdi mdi-plus me-1"></i> Nuevo motivo
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-centered" id="tblReclamos">
                                    <thead class="table-dark">
                                        <tr><th width="5%">#</th><th>Motivo</th><th>Área</th><th width="12%">Opciones</th></tr>
                                    </thead>
                                    <tbody id="bodyReclamos"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ===== MOTIVOS CONSULTA ===== -->
                        <div class="tab-pane" id="tab-consultas">
                            <div class="text-end mb-2">
                                <button class="btn btn-success btn-sm rounded-pill" onclick="abrirModal('consultas', 0)">
                                    <i class="mdi mdi-plus me-1"></i> Nuevo motivo
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-centered" id="tblConsultas">
                                    <thead class="table-dark">
                                        <tr><th width="5%">#</th><th>Motivo</th><th>Área</th><th width="12%">Opciones</th></tr>
                                    </thead>
                                    <tbody id="bodyConsultas"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ===== ÁREAS ===== -->
                        <div class="tab-pane" id="tab-areas">
                            <div class="text-end mb-2">
                                <button class="btn btn-success btn-sm rounded-pill" onclick="abrirModalArea(0)">
                                    <i class="mdi mdi-plus me-1"></i> Nueva área
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-centered" id="tblAreas">
                                    <thead class="table-dark">
                                        <tr><th width="5%">#</th><th>Área</th><th>Teléfono notificaciones</th><th width="8%">Activo</th><th width="12%">Opciones</th></tr>
                                    </thead>
                                    <tbody id="bodyAreas"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ===== TOKEN WHATSAPP ===== -->
                        <div class="tab-pane" id="tab-token">
                            <p class="text-muted mb-3">Credenciales de WhatsApp Business de este tenant. Se guardan cifradas y se aplican de forma inmediata. Por seguridad se muestran ocultas — tocá el <i class="mdi mdi-eye-outline"></i> para verlas.</p>
                            <div style="max-width:700px">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">WA_PHONE_NUMBER_ID</label>
                                    <input type="text" class="form-control font-monospace" id="waPhoneId" placeholder="1181887995003553" autocomplete="off">
                                    <small class="text-muted">ID del número de WhatsApp Business (no es secreto).</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">WA_ACCESS_TOKEN</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control font-monospace" id="waToken" placeholder="EAANk0..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVer('waToken', this)" tabindex="-1" title="Ver / ocultar"><i class="mdi mdi-eye-outline"></i></button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">WA_APP_SECRET</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control font-monospace" id="waAppSecret" placeholder="2ed5ce245d0bc992..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVer('waAppSecret', this)" tabindex="-1" title="Ver / ocultar"><i class="mdi mdi-eye-outline"></i></button>
                                    </div>
                                </div>
                                <button class="btn btn-primary" onclick="guardarToken()">
                                    <i class="mdi mdi-content-save me-1"></i> Guardar credenciales
                                </button>
                            </div>
                        </div>

                    </div><!-- /.tab-content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal áreas -->
    <div class="modal fade" id="modalArea" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAreaTitulo">Área</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="aId">
                    <div class="mb-3">
                        <label class="form-label">Nombre del área</label>
                        <input type="text" class="form-control" id="aArea" placeholder="Ej: Logística">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono de notificaciones</label>
                        <input type="text" class="form-control" id="aTelefono" placeholder="Ej: 5493764278402">
                        <div class="form-text">Número con código de país, sin + ni espacios.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="aActivo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" onclick="guardarArea()">
                        <i class="mdi mdi-content-save me-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal agregar / editar -->
    <div class="modal fade" id="modalMotivo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Motivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="mId">
                    <input type="hidden" id="mTabla">
                    <div class="mb-3">
                        <label class="form-label">Texto del motivo</label>
                        <input type="text" class="form-control" id="mOpcion" placeholder="Ej: Producto dañado en la entrega">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Área responsable</label>
                        <select class="form-select" id="mArea"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" onclick="guardarMotivo()">
                        <i class="mdi mdi-content-save me-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
    require 'footerv1.php';
    ?>
    <script>document.title = "Atiende | Configuración";</script>
    <script>
    var areas = [];
    var modalBS, modalAreaBS;

    document.addEventListener('DOMContentLoaded', function () {
        modalBS     = new bootstrap.Modal(document.getElementById('modalMotivo'));
        modalAreaBS = new bootstrap.Modal(document.getElementById('modalArea'));
        cargarAreas(function () {
            cargarMenuPrincipal();
            cargarTabla('reclamos');
            cargarTabla('consultas');
        });

        // recargar tablas al cambiar de tab
        document.querySelectorAll('#cfgTabs a[data-bs-toggle="tab"]').forEach(function (el) {
            el.addEventListener('shown.bs.tab', function (e) {
                var t = e.target.getAttribute('href');
                if (t === '#tab-reclamos') cargarTabla('reclamos');
                if (t === '#tab-consultas') cargarTabla('consultas');
                if (t === '#tab-areas') cargarTablaAreas();
                if (t === '#tab-token') cargarToken();
            });
        });
        cargarToken();
    });

    function cargarAreas(cb) {
        $.getJSON('../ajax/configuracion.php?op=listarAreas', function (data) {
            areas = data;
            cb && cb();
        });
    }

    // ===== MENÚ PRINCIPAL =====
    function cargarMenuPrincipal() {
        $.getJSON('../ajax/configuracion.php?op=getMenuPrincipal', function (groups) {
            var html = '';
            groups.forEach(function (g) {
                html += '<div class="card border mb-3">';
                html += '<div class="card-header bg-light py-2"><strong>' + escHtml(g.label) + '</strong></div>';
                html += '<div class="card-body p-2">';
                html += '<table class="table table-sm table-bordered mb-2">';
                html += '<thead class="table-light"><tr><th width="8%">Opción</th><th>Texto que ve el usuario</th></tr></thead><tbody>';
                g.items.forEach(function (it) {
                    var lbl = it.opcionId === '0' ? '0 (Salir)' : it.opcionId;
                    html += '<tr><td class="text-center fw-bold">' + lbl + '</td>';
                    html += '<td><input type="text" class="form-control form-control-sm" data-menuid="' + escHtml(g.menuId) + '" data-opcionid="' + escHtml(it.opcionId) + '" value="' + escHtml(it.opcion) + '"></td></tr>';
                });
                html += '</tbody></table>';
                html += '<button class="btn btn-primary btn-sm" onclick="guardarGrupoMenu(\'' + escJs(g.menuId) + '\')">';
                html += '<i class="mdi mdi-content-save me-1"></i>Guardar</button>';
                html += '</div></div>';
            });
            document.getElementById('menuPrincipalList').innerHTML = html || '<p class="text-muted">Sin menús</p>';
        });
    }

    function guardarGrupoMenu(menuId) {
        var items = [];
        document.querySelectorAll('input[data-menuid="' + menuId + '"]').forEach(function (inp) {
            items.push({ opcionId: inp.dataset.opcionid, opcion: inp.value.trim() });
        });
        $.post('../ajax/configuracion.php?op=saveMenuPrincipal', { menuId: menuId, items: JSON.stringify(items) }, function (r) {
            if (r.ok) {
                Swal.fire({ icon: 'success', text: 'Menú actualizado', timer: 1200, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: 'Error al guardar' });
            }
        });
    }

    // ===== MOTIVOS TABLAS =====
    function cargarTabla(tabla) {
        var op    = tabla === 'reclamos' ? 'listarReclamos' : 'listarConsultas';
        var tbody = document.getElementById(tabla === 'reclamos' ? 'bodyReclamos' : 'bodyConsultas');
        $.getJSON('../ajax/configuracion.php?op=' + op, function (res) {
            var html = '';
            res.data.forEach(function (r) {
                html += '<tr>';
                html += '<td class="text-center"><span class="badge bg-secondary">' + r.opcionId + '</span></td>';
                html += '<td>' + escHtml(r.opcion) + '</td>';
                var areaLabel = r.areaNombre || (areas.find(function(a){return parseInt(a.id)===parseInt(r.area);})||{}).area || r.area;
                html += '<td>' + escHtml(areaLabel) + '</td>';
                html += '<td>';
                html += '<button class="btn btn-warning btn-sm btn-icon-line me-1" onclick="abrirModal(\'' + tabla + '\',' + r.id + ',\'' + escJs(r.opcion) + '\',' + r.area + ')" title="Editar"><i class="mdi mdi-lead-pencil m-n2"></i></button>';
                html += '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminarMotivo(\'' + tabla + '\',' + r.id + ')" title="Eliminar"><i class="mdi mdi-trash-can-outline m-n2"></i></button>';
                html += '</td></tr>';
            });
            tbody.innerHTML = html || '<tr><td colspan="4" class="text-center text-muted">Sin registros</td></tr>';
        });
    }

    function abrirModal(tabla, id, opcion, area) {
        document.getElementById('mId').value    = id || 0;
        document.getElementById('mTabla').value = tabla;
        document.getElementById('mOpcion').value = opcion || '';
        document.getElementById('modalTitulo').textContent = id ? 'Editar motivo' : 'Nuevo motivo';

        var sel = document.getElementById('mArea');
        sel.innerHTML = '';
        areas.forEach(function (a) {
            var opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = a.area;
            if (area && parseInt(a.id) === parseInt(area)) opt.selected = true;
            sel.appendChild(opt);
        });

        modalBS.show();
    }

    function guardarMotivo() {
        var opcion = document.getElementById('mOpcion').value.trim();
        if (!opcion) { Swal.fire({ icon: 'warning', text: 'Ingresá el texto del motivo' }); return; }
        $.post('../ajax/configuracion.php?op=guardar', {
            tabla:  document.getElementById('mTabla').value,
            id:     document.getElementById('mId').value,
            opcion: opcion,
            area:   document.getElementById('mArea').value
        }, function (r) {
            if (r.ok) {
                modalBS.hide();
                cargarTabla(document.getElementById('mTabla').value);
                Swal.fire({ icon: 'success', text: 'Guardado', timer: 1200, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: 'Error al guardar' });
            }
        });
    }

    function eliminarMotivo(tabla, id) {
        Swal.fire({
            title: '¿Eliminar este motivo?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fa5c7c',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Eliminar'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.post('../ajax/configuracion.php?op=eliminar', { tabla: tabla, id: id }, function (res) {
                    if (res.ok) {
                        cargarTabla(tabla);
                        Swal.fire({ icon: 'success', text: 'Eliminado', timer: 1200, showConfirmButton: false });
                    }
                });
            }
        });
    }

    // ===== ÁREAS =====
    function cargarTablaAreas() {
        $.getJSON('../ajax/configuracion.php?op=listarAreasAdmin', function (res) {
            var html = '';
            res.data.forEach(function (r) {
                var badge = r.activo == 1
                    ? '<span class="badge bg-success">Sí</span>'
                    : '<span class="badge bg-secondary">No</span>';
                html += '<tr>';
                html += '<td class="text-center"><span class="badge bg-secondary">' + r.id + '</span></td>';
                html += '<td>' + escHtml(r.area) + '</td>';
                html += '<td><code>' + escHtml(r.telefono) + '</code></td>';
                html += '<td class="text-center">' + badge + '</td>';
                html += '<td>';
                html += '<button class="btn btn-warning btn-sm btn-icon-line me-1" onclick="abrirModalArea(' + r.id + ',\'' + escJs(r.area) + '\',\'' + escJs(r.telefono) + '\',' + r.activo + ')" title="Editar"><i class="mdi mdi-lead-pencil m-n2"></i></button>';
                html += '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminarArea(' + r.id + ')" title="Eliminar"><i class="mdi mdi-trash-can-outline m-n2"></i></button>';
                html += '</td></tr>';
            });
            document.getElementById('bodyAreas').innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">Sin registros</td></tr>';
        });
    }

    function abrirModalArea(id, area, telefono, activo) {
        document.getElementById('aId').value       = id || 0;
        document.getElementById('aArea').value     = area || '';
        document.getElementById('aTelefono').value = telefono || '';
        document.getElementById('aActivo').value   = (activo !== undefined) ? activo : 1;
        document.getElementById('modalAreaTitulo').textContent = id ? 'Editar área' : 'Nueva área';
        modalAreaBS.show();
    }

    function guardarArea() {
        var area     = document.getElementById('aArea').value.trim();
        var telefono = document.getElementById('aTelefono').value.trim();
        if (!area || !telefono) { Swal.fire({ icon: 'warning', text: 'Completá todos los campos' }); return; }
        $.post('../ajax/configuracion.php?op=guardarArea', {
            id:       document.getElementById('aId').value,
            area:     area,
            telefono: telefono,
            activo:   document.getElementById('aActivo').value
        }, function (r) {
            if (r.ok) {
                modalAreaBS.hide();
                cargarTablaAreas();
                cargarAreas(function(){});
                Swal.fire({ icon: 'success', text: 'Guardado', timer: 1200, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: 'Error al guardar' });
            }
        });
    }

    function eliminarArea(id) {
        Swal.fire({
            title: '¿Eliminar esta área?',
            text: 'Los motivos asignados a esta área quedarán sin área.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fa5c7c',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Eliminar'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.post('../ajax/configuracion.php?op=eliminarArea', { id: id }, function (res) {
                    if (res.ok) {
                        cargarTablaAreas();
                        cargarAreas(function(){});
                        Swal.fire({ icon: 'success', text: 'Eliminada', timer: 1200, showConfirmButton: false });
                    }
                });
            }
        });
    }

    // ===== TOKEN WHATSAPP =====
    function cargarToken() {
        $.getJSON('../ajax/configuracion.php?op=getToken', function (r) {
            if (r && r.ok) {
                document.getElementById('waPhoneId').value    = r.phoneId || '';
                document.getElementById('waToken').value      = r.token || '';
                document.getElementById('waAppSecret').value  = r.appSecret || '';
            }
        });
    }

    function toggleVer(id, btn) {
        var el = document.getElementById(id);
        var icon = btn.querySelector('i');
        if (el.type === 'password') { el.type = 'text';     icon.className = 'mdi mdi-eye-off-outline'; }
        else                        { el.type = 'password'; icon.className = 'mdi mdi-eye-outline'; }
    }

    function guardarToken() {
        var phoneId   = document.getElementById('waPhoneId').value.trim();
        var token     = document.getElementById('waToken').value.trim();
        var appSecret = document.getElementById('waAppSecret').value.trim();
        if (!token && !appSecret && !phoneId) { Swal.fire({ icon: 'warning', text: 'Completá al menos un campo' }); return; }
        $.post('../ajax/configuracion.php?op=saveToken', { token: token, appSecret: appSecret, phoneId: phoneId }, function (r) {
            if (r.ok) {
                // re-enmascarar y refrescar desde la DB
                document.getElementById('waToken').type     = 'password';
                document.getElementById('waAppSecret').type = 'password';
                cargarToken();
                Swal.fire({ icon: 'success', text: 'Credenciales actualizadas', timer: 1800, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: r.error || 'Error al guardar' });
            }
        });
    }

    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function escJs(s)   { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
    </script>
    <?php
}
ob_end_flush();
?>
