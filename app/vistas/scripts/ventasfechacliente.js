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
		"aServerSide": true,//paginacion y filrado realizados por el server
		dom: 'Bfrtip',//definimos los elementos del control de la tabla
		buttons: [],
		"ajax":
			{
				url:'../ajax/consultas.php?op=ventasfechacliente',
				data:{fecha_inicio:fecha_inicio, fecha_fin:fecha_fin, idcliente: idcliente},
				type: "get",
				dataType : "json",
			},
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