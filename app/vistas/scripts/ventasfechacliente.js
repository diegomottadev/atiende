var tabla;

//funcion que se ejecuta al inicio

function aExcel(){
	var  fecha_inicio = $("#fecha_inicio").val();
	var fecha_fin = $("#fecha_fin").val();
	var idcliente = $("#idcliente").val();
	$(location).attr('href',"../ajax/aExcelConsulta.php?fecha_inicio="+fecha_inicio+"&fecha_fin="+fecha_fin+"&idcliente="+idcliente);

}

function init(){

	listar();
	//cargamos los items al select cliente
	$.post("../ajax/venta.php?op=selectCliente", function(r){
		$("#idcliente").html(r);
		//$('#idcliente').selectpicker('refresh');
	});

	//placeholder del buscador interno del select2 Cliente
	$("#idcliente").on('select2:open', function(){
		$('.select2-container--open .select2-search__field').attr('placeholder', 'Ingrese nombre de cliente');
	});

	// Sin botón "Buscar": al cambiar fecha o cliente se re-consulta automáticamente
	$("#fecha_inicio, #fecha_fin").on('change', function(){ listar(); });
	$("#idcliente").on('change', function(){ listar(); });

	// Buscador de texto sobre el resultado: se activa recién a partir de 3 caracteres
	$("#fBuscar").on('input', function(){
		var v = (this.value || '').trim();
		if (!tabla) return;
		// >=3 caracteres → filtra; menos de 3 → muestra todo (no busca)
		tabla.search(v.length >= 3 ? v : '').draw();
	});

	// Borrar filtros: vuelve a los valores por defecto (fechas = hoy, sin cliente, sin búsqueda)
	$("#fLimpiar").on('click', function(){
		var hoy = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD en hora local
		$("#fBuscar").val('');
		$("#fecha_inicio").val(hoy);
		$("#fecha_fin").val(hoy);
		$("#idcliente").val('').trigger('change.select2'); // resetea el select2 sin disparar listar()
		if (tabla) tabla.search('');
		listar();
	});

}

//funcion listar
function listar(){
	var  fecha_inicio = $("#fecha_inicio").val();
	var fecha_fin = $("#fecha_fin").val();
	var idcliente = $("#idcliente").val();

	tabla=$('#tbllistado').dataTable({
		drawCallback:function(){
            $(".dataTables_paginate > .pagination").addClass("pagination-rounded")
        },
		"language": lenguajeTable,
		"aProcessing": true,//activamos el procedimiento del datatable
		"aServerSide": false,// el server devuelve TODO el set (filtrado por fecha/cliente); DataTables busca/ordena/pagina del lado del cliente
		dom: 'rtip',// sin 'f' (búsqueda propia en la barra de filtros) ni 'B' (sin botones)
		responsive: window.matchMedia('(max-width: 991.98px)').matches,// solo en mobile (<992px, incluye tablets en vertical); en desktop, todas las columnas. Originalmente: colapsa columnas que no entran en una fila expandible (+)
		"ajax":
			{
				url:'../ajax/consultas.php?op=ventasfechacliente',
				data:{fecha_inicio:fecha_inicio, fecha_fin:fecha_fin, idcliente: idcliente},
				type: "get",
				dataType : "json",
				dataSrc: "aaData"// el endpoint devuelve las filas en aaData
			},
		// Conservar el término del buscador (≥3) al re-consultar por fecha/cliente
		"search": { "search": ($("#fBuscar").val() || "").trim().length >= 3 ? $("#fBuscar").val().trim() : "" },
		"initComplete":function( settings, json){
			// console.log(json);
			$("#pedidos").text(json.totalPedido + "");
			$("#importe").text(json.totalImporte + "");
		},
		"bDestroy":true,
		"iDisplayLength":10,//paginacion
		"order":[[0,"desc"]]//ordenar (columna, orden)
	}).DataTable();

	//var oSettings = tabla.fnSettings();
	//console.log("info:"+tabla.toString());
}


init();  