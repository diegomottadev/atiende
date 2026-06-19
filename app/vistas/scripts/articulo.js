var tabla;

//funcion que se ejecuta al inicio
function init(){
   mostrarform(false);
   listar();

   $("#formulario").on("submit",function(e){
   	guardaryeditar(e);
   })

   //cargamos los items al celect categoria
   $.post("../ajax/articulo.php?op=selectCategoria", function(r){
   	$("#idcategoria").html(r);
   	$("#idcategoria").selectpicker('refresh');
   });
   

   
   // Preview + validación de la imagen (mismo patrón que el form de usuarios)
   $("#imagen").change(function(){
      var f = this.files && this.files[0];
      if(!f) return;
      if(['image/jpeg','image/jpg'].indexOf(f.type) === -1){
         Swal.fire({icon:'error', title:'Formato no permitido', text:'Solo se aceptan imágenes JPG.'});
         limpiarImagen(); return;
      }
      if(f.size > 2*1024*1024){
         Swal.fire({icon:'error', title:'Imagen demasiado grande', text:'El máximo permitido es 2 MB.'});
         limpiarImagen(); return;
      }
      readURL(this);
   });

   // Click en la miniatura de la tabla → modal con la imagen ampliada
   $(document).on('click', '#tbllistado tbody img', function () {
      var data = tabla.row($(this).closest('tr')).data();
      if (!data) return;
      var codigo = data[1] || '', descripcion = data[2] || '', rubro = data[11] || '', subrubro = data[12] || '';
      var titulo = [rubro, subrubro].filter(function (x) { return x && String(x).trim() !== ''; }).join(' - ');
      $('#modalImgTitulo').text(titulo || 'Artículo');
      $('#modalImgSub').text((codigo ? codigo : '') + (descripcion ? '  ·  ' + descripcion : ''));
      $('#modalImgFoto').attr('src', globalArticulosDir + codigo + '.jpg?im=' + (new Date()).getTime()).attr('alt', descripcion);
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalImgArticulo')).show();
   });

   // --- Filtros profesionales (server-side: la DB busca/filtra sobre TODO el dataset) ---
   function filtrarColumna(idx, val){ tabla.column(idx).search(val || '').draw(); } // exact match en el server
   var buscarTimer;
   $('#fBuscar').on('keyup input', function(){
      var v = this.value;
      clearTimeout(buscarTimer);
      buscarTimer = setTimeout(function(){ tabla.search(v).draw(); }, 350); // debounce: no pegar al server por cada tecla
   });
   $('#fRubro').on('change',    function(){ filtrarColumna(11, this.value); });
   $('#fSubrubro').on('change', function(){ filtrarColumna(12, this.value); });
   $('#fLinea').on('change',    function(){ filtrarColumna(10, this.value); });
   $('#fMarca').on('change',    function(){ filtrarColumna(13, this.value); });
   $('#fLimpiar').on('click', function(){
      $('#fBuscar').val('');
      $('#fRubro,#fSubrubro,#fLinea,#fMarca').val('');
      tabla.search('').columns([10,11,12,13]).search('').draw();
   });
   // Poblar los dropdowns con los valores distintos (desde el server, no solo la página visible)
   $.get('../ajax/articulo.php?op=filtros', function(r){
      try { if (typeof r === 'string') r = JSON.parse(r); } catch(e){ return; }
      function fill(sel, arr){
         var $s = $(sel); if (!$s.length) return;
         $s.find('option:not(:first)').remove();
         (arr || []).forEach(function(v){ if (v !== null && String(v).trim() !== '') $s.append($('<option>').attr('value', v).text(v)); });
      }
      fill('#fRubro', r.rubro); fill('#fSubrubro', r.subrubro); fill('#fLinea', r.linea); fill('#fMarca', r.marca);
   }, 'json');
}

//funcion limpiar
function limpiar(){
	$("#codigo").val("");
	$("#nombre").val("");
	$("#descripcion").val("");
	$("#subrubro").val("");
	$("#marca").val("");
	$("#stock").val("");
	$("#imagenmuestra").attr("src","").hide();
	$("#imagenactual").val("");
	$("#imagen").val("");
	$("#btnQuitarImagen").hide();
	$("#barcodeWrap").hide();
	$("#idarticulo").val("");
}

//funcion mostrar formulario
function mostrarform(flag){
	limpiar();
	if(flag){
		$("#panelLista").hide();
		$("#listadoregistros").hide();
		$("#filtrosArticulo").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",true);
		$('#formulario').off('input.gd change.gd').on('input.gd change.gd', 'input, select, textarea', function(){ $('#btnGuardar').prop('disabled', false); });
		$("#btnagregar").hide();
		$("#btnExportar").hide();
		$("#btnAbrirImportar").hide();
		$("#btnAbrirImportarImg").hide();
		$('#btnCancel').show();
	}else{
		$('#btnCancel').hide();
		$("#panelLista").show();
		$("#listadoregistros").show();
		$("#filtrosArticulo").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
		$("#btnExportar").show();
		$("#btnAbrirImportar").show();
		$("#btnAbrirImportarImg").show();

	}
}

//cancelar form
function cancelarform(){
	limpiar();
	mostrarform(false);
}

//funcion listar
function listar(){
	tabla=$('#tbllistado').dataTable({
		drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            document.querySelectorAll('#tbllistado [data-bs-toggle="tooltip"]').forEach(function(el){
                try {
                    var existing = bootstrap.Tooltip.getInstance(el);
                    if (existing) { existing.hide(); existing.dispose(); }
                    new bootstrap.Tooltip(el, {trigger: 'hover', animation: false});
                } catch(e) {}
            });
        },
		"columnDefs":[
			{ "orderable": false, "targets": 0 },
			{ "visible": false, "targets": [4,5,6,7,8,9, 14,15,16,17,18,19,20,21,22,23,24,25,26,27] } // ocultas por defecto: lista2–lista7 y de kilos hasta orden
		],
		"language": lenguajeTable,
		"aProcessing": true,//activamos el procedimiento del datatable
		"aServerSide": true,// server-side: la DB hace búsqueda/orden/paginado → escala a millones de filas
		dom: 'rtip',//sin 'f' (buscador propio); el selector de columnas se monta en la barra de filtros (#colvisHost), no en .dt-buttons
		responsive: window.matchMedia('(max-width: 991.98px)').matches,//solo en mobile (<992px, incluye tablets en vertical); en desktop, todas las columnas. Convive con el colvis. Originalmente: colapsa columnas que no entran en una fila expandible (+). Convive con el colvis propio.
		buttons: [],
		"initComplete": function (settings, json) {
			var api = this.api();
			var LISTAS = [4,5,6,7,8,9]; // índices de lista2…lista7

			// --- Persistencia de la selección de columnas en localStorage (por tenant) ---
			var STORAGE_KEY = 'atiende.colvis.articulo.' + (typeof globalNombreEmpresa !== 'undefined' ? globalNombreEmpresa : '');
			var listasMemo;
			(function restaurarEstadoGuardado() {
				try {
					var saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
					if (saved && saved.v) {
						api.columns().every(function () {
							var i = this.index();
							if (i !== 0 && typeof saved.v[i] === 'boolean') this.visible(saved.v[i], false);
						});
						api.columns.adjust();
					}
					listasMemo = (saved && saved.memo && saved.memo.length)
						? saved.memo
						: LISTAS.filter(function (c) { return api.column(c).visible(); });
				} catch (e) {
					listasMemo = LISTAS.filter(function (c) { return api.column(c).visible(); });
				}
			})();
			function guardarColvis() {
				try {
					var v = {};
					api.columns().every(function () { var i = this.index(); if (i !== 0) v[i] = this.visible(); });
					localStorage.setItem(STORAGE_KEY, JSON.stringify({ v: v, memo: listasMemo }));
				} catch (e) {}
			}

			// 1) Atajo en el header de lista1: chevron que colapsa/expande SOLO las listas seleccionadas (lista1 queda fija)
			var th = $(api.column(3).header());
			if (!th.find('.toggle-listas').length) {
				var chev = $('<button type="button" class="btn btn-sm btn-link p-0 ms-1 align-baseline toggle-listas" '
					+ 'data-bs-toggle="tooltip" data-bs-trigger="hover" title="Mostrar/ocultar las listas seleccionadas" '
					+ 'style="text-decoration:none;color:#727cf5;line-height:1;">'
					+ '<i class="mdi mdi-chevron-double-right"></i></button>');
				chev.on('click', function (e) {
					e.stopPropagation(); // no disparar el ordenamiento de la columna
					var visibles = LISTAS.filter(function (c) { return api.column(c).visible(); });
					if (visibles.length) {
						listasMemo = visibles;               // recordar la selección actual
						api.columns(LISTAS).visible(false);  // colapsar (se guarda)
					} else {
						// expandir: las seleccionadas; si nunca elegiste, mostrar todas (se guarda)
						api.columns(listasMemo.length ? listasMemo : LISTAS).visible(true);
					}
				});
				th.append(chev);
				// reflejar el estado restaurado en el ícono del chevron
				chev.find('i').attr('class', LISTAS.some(function (c) { return api.column(c).visible(); }) ? 'mdi mdi-chevron-double-left' : 'mdi mdi-chevron-double-right');
				try { new bootstrap.Tooltip(chev[0], {trigger:'hover', animation:false}); } catch (e) {}
			}

			// 2) Selector de columnas: dropdown con buscador + un check por columna (mostrar/ocultar)
			var dd = $('<div class="dropdown d-inline-block">'
				+ '<button class="btn btn-sm btn-soft-secondary dropdown-toggle" type="button" '
				+ 'data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">'
				+ '<i class="mdi mdi-view-column-outline"></i> Columnas</button>'
				+ '<div class="dropdown-menu p-2 colvis-menu" style="min-width:230px;">'
				+ '<input type="text" class="form-control form-control-sm mb-2 colvis-search" placeholder="Buscar columna...">'
				+ '<div class="colvis-list" style="max-height:280px;overflow:auto;"></div>'
				+ '<div class="colvis-empty text-muted small px-2 py-1" style="display:none;">Sin coincidencias</div>'
				+ '</div></div>');
			var list = dd.find('.colvis-list');
			api.columns().every(function () {
				var idx = this.index();
				if (idx === 0) return; // saltar la columna de acción (botón editar)
				// título = solo el texto del header (sin el chevron que inyectamos en lista1)
				var titulo = $(this.header()).clone().children().remove().end().text().trim() || ('Columna ' + idx);
				list.append($('<label class="dropdown-item d-flex align-items-center gap-2 px-2 py-1 mb-0" style="cursor:pointer;">'
					+ '<input type="checkbox" class="form-check-input m-0 colvis-chk" value="' + idx + '" ' + (this.visible() ? 'checked' : '') + '>'
					+ '<span class="text-capitalize">' + titulo + '</span></label>'));
			});
			list.on('change', '.colvis-chk', function () {
				api.column(parseInt(this.value, 10)).visible(this.checked);
			});
			// Buscador: filtra las columnas por su nombre en vivo
			var $search = dd.find('.colvis-search');
			$search.on('click', function (e) { e.stopPropagation(); }); // tipear no cierra el dropdown
			$search.on('keyup input', function () {
				var q = $(this).val().toLowerCase().trim();
				var visibles = 0;
				list.find('label').each(function () {
					var match = $(this).text().toLowerCase().indexOf(q) !== -1;
					$(this).toggle(match);
					if (match) visibles++;
				});
				dd.find('.colvis-empty').toggle(visibles === 0);
			});
			// Al abrir: limpiar filtro y enfocar el buscador
			dd.on('shown.bs.dropdown', function () { $search.val('').trigger('input').trigger('focus'); });
			// Sincronizar checkboxes + ícono del chevron ante CUALQUIER cambio de visibilidad
			api.off('column-visibility.dt.colvis').on('column-visibility.dt.colvis', function (e, s, column, state) {
				list.find('.colvis-chk[value="' + column + '"]').prop('checked', state);
				th.find('.toggle-listas i').attr('class', LISTAS.some(function (c) { return api.column(c).visible(); }) ? 'mdi mdi-chevron-double-left' : 'mdi mdi-chevron-double-right');
				guardarColvis(); // persistir la selección en localStorage
			});
			$('#colvisHost').append(dd); // cluster "controles de vista" en la barra de filtros (no flotando sobre la tabla)
		},
		"ajax":
		{
			url:'../ajax/articulo.php?op=listar',
			type: "post",// POST: los params de DataTables (28+ columnas) no entran cómodos en la URL
			dataType : "json",
			error:function(e){
				console.log(e.responseText);
			}
		},
		"bDestroy":true,
		"iDisplayLength":10,
		"order":[[1,"desc"]]//ordenar por el id (ahora columna 1; la 0 es el botón editar)
	}).DataTable();
}
//funcion para guardaryeditar
function guardaryeditar(e){
     e.preventDefault();//no se activara la accion predeterminada 
     $("#btnGuardar").prop("disabled",true);
     var formData=new FormData($("#formulario")[0]);
     
     $.ajax({
     	url: "../ajax/articulo.php?op=guardaryeditar",
     	type: "POST",
     	data: formData,
     	contentType: false,
     	processData: false,

     	success: function(datos){
			Swal.fire({                    
				text: datos
			});
     		mostrarform(false);
     		tabla.ajax.reload();
     	}
     });

     limpiar();
}

function mostrar(idarticulo){
/*
    "codigo": "623",
    "descripcion": "Melon blanco BRASIL (unidad)",
    "lista1": "200",
    "linea": "FRUTAS",
    "rubro": "A-FRUTAS",
    "capacidad": "1",
    "pack": "1",
    "impInt": "",
    "codBarra": "",
    "calibre": ""
*/

	   $.post("../ajax/articulo.php?op=mostrar",{idarticulo : idarticulo},
		function(data,status)
		{
			data=JSON.parse(data);
			mostrarform(true);

			$("#codigo").val(data.codigo);
			$("#nombre").val(data.descripcion);
			$("#rubro").val(data.rubro);
			$("#linea").val(data.linea);
			$("#subrubro").val(data.subrubro);
			$("#marca").val(data.marca);
			$("#imagenmuestra").attr("src",globalArticulosDir+data.codigo+".jpg?im="+(new Date()).getTime()).show();
			$("#btnQuitarImagen").show();
			try { bootstrap.Tooltip.getOrCreateInstance(document.getElementById('btnQuitarImagen'), {trigger:'hover', animation:false}); } catch(err){}
			$("#imagenactual").val(data.imagen);
			$("#idarticulo").val(data.codigo);
			dibujarBarcode();
		})
}


//funcion para desactivar
function desactivar(idarticulo){
	bootbox.confirm("¿Esta seguro de desactivar este dato?", function(result){
		if (result) {
			$.post("../ajax/articulo.php?op=desactivar", {idarticulo : idarticulo}, function(e){
				Swal.fire({                    
					text: e
				});
				tabla.ajax.reload();
			});
		}
	})
}

function activar(idarticulo){
	bootbox.confirm("¿Esta seguro de activar este dato?" , function(result){
		if (result) {
			$.post("../ajax/articulo.php?op=activar" , {idarticulo : idarticulo}, function(e){
				Swal.fire({                    
					text: e
				});
				tabla.ajax.reload();
			});
		}
	})
}

// Dibuja el barcode sin spinner (uso interno: al abrir el form de edición)
function dibujarBarcode(){
	var codigo=$("#codigo").val();
	if(!codigo) return;
	try { JsBarcode("#barcode",codigo); $("#barcodeWrap").show(); } catch(e){}
}
// Click manual en "Generar": deshabilita + spinner mientras genera, luego restaura
function generarbarcode(){
	if(!$("#codigo").val()) return;
	var $btn=$("#btnGenerar");
	$btn.prop("disabled",true);
	$("#genSpinner").removeClass("d-none");
	$("#genIcon").addClass("d-none");
	setTimeout(function(){
		dibujarBarcode();
		$btn.prop("disabled",false);
		$("#genSpinner").addClass("d-none");
		$("#genIcon").removeClass("d-none");
	}, 600);
}

/* Preview de la imagen seleccionada (mismo patrón que el form de usuarios) */
function readURL(input){
	if(input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function(e){
			$('#imagenmuestra').attr('src', e.target.result).show();
			$('#btnQuitarImagen').show();
			try { bootstrap.Tooltip.getOrCreateInstance(document.getElementById('btnQuitarImagen'), {trigger:'hover', animation:false}); } catch(err){}
		};
		reader.readAsDataURL(input.files[0]);
	}
}
/* Quitar la imagen seleccionada → vuelve a la imagen actual del producto (o la oculta) */
function limpiarImagen(){
	var t = bootstrap.Tooltip.getInstance(document.getElementById('btnQuitarImagen')); if(t){ t.hide(); }
	$('#imagen').val('');
	var cod = $('#codigo').val();
	if(cod){ $('#imagenmuestra').attr('src',globalArticulosDir+cod+'.jpg?im='+(new Date()).getTime()).show(); }
	else   { $('#imagenmuestra').hide().attr('src',''); }
	$('#btnQuitarImagen').hide();
}
$(document).on('click', '#btnQuitarImagen', limpiarImagen);

function imprimir(){
	$("#print").printArea();
}

$(function(){
	$("#formUp").on("submit", function(e){
		e.preventDefault();
		var f = $(this);
		var formData = new FormData(document.getElementById("formUp"));
		formData.append("dato", "valor");
		//formData.append(f.attr("name"), $(this)[0].files[0]);
		$.ajax({
			url: "../ajax/subirarchivo.php",
			type: "post",
			dataType: "html",
			data: formData,
			cache: false,
			contentType: false,
	 		processData: false
		}).then(function(res){

			if(res.includes("Fatal error")){
				$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/articulos-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
				return;
			}
			json = (JSON.parse(res));

			if(json.status == 404){
				$("#mensaje").html('<div class="alert alert-danger" role="alert">'+ json.error +'</div>');
				return;
			}

			$("#mensaje").html('<div class="alert alert-success" role="alert">'+ json.msj +'</div>');
			listar();
		}).catch(function (res) {
			$("#mensaje").html('<div class="alert alert-danger" role="alert">El archivo importado contiene un formato incorrecto. Verifique con el ejemplo: <a href="../../public/examples/articulos-demostracion-exportacion.xlsx" target="_blank"> articulos-demostracion-exportacion.xlsx </a></div>');
		});
	});

	// ===== Importación masiva de imágenes de artículos (carpeta + lotes) =====
	// La carpeta puede traer cualquier archivo; nos quedamos solo con los JPG por extensión
	// (el atributo accept no filtra en modo webkitdirectory).
	var imgSeleccionadas = []; // lista filtrada de File a subir

	function resetImgContadores() {
		$("#imgOk").text(0);
		$("#imgNomatch").text(0);
		$("#imgErr").text(0);
		$("#imgMensaje").empty();
		$("#imgProgress").addClass("d-none");
		$("#imgProgressBar").css("width", "0%").attr("aria-valuenow", 0);
		$("#imgProgressText").text("0 / 0");
	}

	function limpiarSeleccionImg() {
		imgSeleccionadas = [];
		$("#imgFiles").val("");
		$("#uploadFilenameImg").addClass("d-none").text("");
		$("#uploadLabelImg").removeClass("d-none");
		$("#uploadIconImg").attr("class", "mdi mdi-folder-image upload-zone__icon");
		$("#uploadZoneImg").removeClass("has-file");
		$("#uploadClearImg").addClass("d-none");
		$("#imgDetectadas").text("0 imágenes detectadas");
		$("#btnImportarImg").prop("disabled", true);
		resetImgContadores();
	}

	$("#imgFiles").on("change", function(){
		// Filtrar a JPG por extensión (accept no aplica en modo carpeta).
		var files = this.files ? Array.prototype.slice.call(this.files) : [];
		imgSeleccionadas = files.filter(function(f){ return /\.jpe?g$/i.test(f.name); });
		var n = imgSeleccionadas.length;
		$("#imgDetectadas").text(n + (n === 1 ? " imagen detectada" : " imágenes detectadas"));
		if (n > 0) {
			$("#uploadFilenameImg").text(n + (n === 1 ? " imagen lista para importar" : " imágenes listas para importar")).removeClass("d-none");
			$("#uploadLabelImg").addClass("d-none");
			$("#uploadIconImg").attr("class", "mdi mdi-folder-image-outline upload-zone__icon");
			$("#uploadZoneImg").addClass("has-file");
			$("#uploadClearImg").removeClass("d-none");
			$("#btnImportarImg").prop("disabled", false);
		} else {
			// La carpeta no tenía JPG: limpiar y avisar.
			limpiarSeleccionImg();
			$("#imgMensaje").html('<div class="alert alert-warning mb-0" role="alert">La carpeta seleccionada no contiene imágenes JPG.</div>');
		}
	});

	$("#uploadClearImg").on("click", function(e){
		e.stopPropagation();
		limpiarSeleccionImg();
	});

	// Arma lotes acumulando archivos hasta 40MB de bytes O 20 archivos (lo primero que se cumpla);
	// nunca un lote vacío (un archivo solo > 40MB se manda solo).
	function armarLotes(files) {
		var MAX_BYTES = 40 * 1024 * 1024;
		var MAX_COUNT = 20;
		var lotes = [];
		var actual = [];
		var bytes = 0;
		for (var i = 0; i < files.length; i++) {
			var f = files[i];
			if (actual.length > 0 && (bytes + f.size > MAX_BYTES || actual.length >= MAX_COUNT)) {
				lotes.push(actual);
				actual = [];
				bytes = 0;
			}
			actual.push(f);
			bytes += f.size;
		}
		if (actual.length > 0) lotes.push(actual);
		return lotes;
	}

	$("#btnImportarImg").on("click", function(){
		if (!imgSeleccionadas.length) return;
		var btn = $(this);
		var total = imgSeleccionadas.length;
		var lotes = armarLotes(imgSeleccionadas);

		var okCount = 0, nomatchCount = 0, errCount = 0, procesadas = 0;

		btn.prop("disabled", true);
		$("#uploadClearImg").prop("disabled", true);
		resetImgContadores();
		$("#imgProgress").removeClass("d-none");
		$("#imgProgressText").text("0 / " + total);

		function actualizarUI() {
			$("#imgOk").text(okCount);
			$("#imgNomatch").text(nomatchCount);
			$("#imgErr").text(errCount);
			var pct = total > 0 ? Math.round((procesadas / total) * 100) : 0;
			$("#imgProgressBar").css("width", pct + "%").attr("aria-valuenow", pct);
			$("#imgProgressText").text(procesadas + " / " + total);
		}

		function contarResultados(results) {
			for (var i = 0; i < results.length; i++) {
				var st = results[i].status;
				if (st === "ok") okCount++;
				else if (st === "nomatch") nomatchCount++;
				else errCount++; // badformat | error → "con error"
			}
		}

		// Subir lotes SECUENCIALMENTE.
		(async function(){
			for (var l = 0; l < lotes.length; l++) {
				var lote = lotes[l];
				var fd = new FormData();
				for (var j = 0; j < lote.length; j++) {
					fd.append("imagenes[]", lote[j], lote[j].name);
				}
				try {
					var resp = await fetch("../ajax/subirimagenes.php", {
						method: "POST",
						body: fd,
						// requireCsrf() acepta el header X-CSRF-Token (mismo que $.ajaxSetup global).
						headers: { "X-CSRF-Token": (typeof globalCsrfToken !== "undefined" ? globalCsrfToken : "") }
					});
					var json = await resp.json();
					var results = (json && json.results) ? json.results : [];
					contarResultados(results);
					// Si el server devolvió menos resultados que archivos del lote, contar el resto como error.
					if (results.length < lote.length) errCount += (lote.length - results.length);
				} catch (err) {
					// Fallo de red / parseo: todo el lote cuenta como error y se sigue.
					errCount += lote.length;
				}
				procesadas += lote.length;
				actualizarUI();
			}

			// Resumen final: danger solo si nada se importó y hubo errores reales; success en el resto.
			if (okCount === 0 && nomatchCount === 0 && errCount > 0) {
				$("#imgMensaje").html('<div class="alert alert-danger mb-0" role="alert">No se pudo importar ninguna imagen.</div>');
			} else {
				$("#imgMensaje").html('<div class="alert alert-success mb-0" role="alert">' +
					okCount + ' imágenes importadas, ' + nomatchCount + ' sin artículo, ' + errCount + ' con error.</div>');
			}

			// Recargar la tabla sin perder la página actual.
			try { if (typeof tabla !== "undefined" && tabla.ajax) tabla.ajax.reload(null, false); } catch (e) {}

			btn.prop("disabled", false);
			$("#uploadClearImg").prop("disabled", false);
		})();
	});
});

function exportarArticulos(){
	$(location).attr('href',"../ajax/exports/exportarArticulos.php");
}

init();