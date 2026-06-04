<!DOCTYPE html>
<html lang="en">
<head>
    <title>Atiende | Pedido</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="../../public/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/css/atiende.css">
    <link rel="stylesheet" href="../../public/sweetAlert2/sweetalert2.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    <script src="../../public/js/jquery.min.js"></script>
    <script src="../../public/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="../../public/sweetAlert2/sweetalert2.all.min.js"></script>
</head>

<script>

function NoBack(){
  history.go(1);
}


 function enviarFormulario(){

     if($("#form_01")[0].checkValidity()) {
         Swal.fire({
             title:'',
             text: '¿Desea enviar el mensaje de respuesta?',
             icon: 'question',
             showCancelButton: true,
             confirmButtonColor: '#727cf5',
             cancelButtonColor: '#fa5c7c',
             cancelButtonText: 'Cancelar',
             confirmButtonText: 'Aceptar'
         }).then((result) => {
             if (result.isConfirmed) {
                 document.getElementById('bloquea').style. display='block';
                 enviarRespuesta();
             }
         })
     }
     else {
         $("#form_01")[0].reportValidity();
     }

 }
 
function PadLeft(value, length) {
    return (value.toString().length < length) ? PadLeft("0" + value, length) : 
    value;
} 
 
function mostrarSaludo(){
	var	ahora=new Date(); 
	var hora=ahora.getHours();
    var texto="";
	if(hora<12){
		texto="Buenos Días";  

		}
  if(hora>12 && hora<18){
		texto="Buenas Tardes";

		}

		if(hora>18 && hora<24){
		texto="Buenas Noches";

	}

return texto;

} 
 
 
 
//-----------------------------------------------------------------------------------
 function enviarRespuesta(){
 /*
     	var json = {
				reclamoId: document.getElementById('reclamoId').value,
				resolucion : document.getElementById('resolucion').value,
				estado: _estado,
			};
*/
		$.ajax({
			type: "POST",
			url: '../../ajax/venta.php?op=guardarMensajeCliente',
			data: "idventa=<?php echo $_GET["idventa"]; ?>&clienteid=<?php echo $_GET["id"]; ?>&mensaje="+document.getElementById('resolucion').value+"&tipo=0" ,
			success: function(data){
			    document.getElementById('bloquea').style.display='none';
                document.location.href="finaliza.php"
            }
		});
			

	//	 openWSConnection('54.175.225.1','8080','/wsbot/ws',JSON.stringify(json));
		 

    
 } 
 function fechaHora () {
  now = new Date();
  year = "" + now.getFullYear();
  month = "" + (now.getMonth() + 1); if (month.length == 1) { month = "0" + month; }
  day = "" + now.getDate(); if (day.length == 1) { day = "0" + day; }
  hour = "" + now.getHours(); if (hour.length == 1) { hour = "0" + hour; }
  minute = "" + now.getMinutes(); if (minute.length == 1) { minute = "0" + minute; }
  second = "" + now.getSeconds(); if (second.length == 1) { second = "0" + second; }
  return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;
}
 

</script>

    <body OnLoad="NoBack();">
        <div class="container">
            <div class="page-header" style="margin-bottom:-0.8%">
                <h4> <strong>Mensaje para el pedido</strong></h4>
            </div>
            <div class="panel panel-primary panel-sombra">
                <div class="panel-body">
                    <form id="form_01">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label >Respuesta</label>                                 
                                    <textarea class="form-control sombra" rows="5" id="resolucion"  required="required"  ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary btn-gradient sombra pull-right" onclick="enviarFormulario();"> 
                                    <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
                                    Enviar
                                </button>
                            </div>                        
                        </div>
                    </form>
                </div>
            </div>  
            <div id="bloquea" class="cargando" style="display:none;">
                <img style="margin-left: 5%;margin-top: 15%" alt="Espere..." src="loading.gif" />
                <div align="center">
                    <h3>Espere un momento...</h3>
                </div>
            </div>
        </div> 
    </body>
</html>