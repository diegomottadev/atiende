<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head runat="server">
    <title></title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"/>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>


    <style>
        .boton{
            width: 150px;
            height:50px;
            margin-left:10px;
            margin-top:10px;
        }

        .boton2{
            width: 150px;
            height:50px;
            margin-left:10px;
        }



    </style>

</head>
<body>

    <form id="form1" runat="server">
    </form>
  
    <div id="prueba" style="text-align:center; padding-top:10px"></div>
    <div id="prueba2" style="text-align:center; padding-top:10px"></div>
    <div id="prueba3" style="text-align:center; padding-top:10px"></div>
    
    <div  style="text-align:center;">
        <button id="btnBajar" type="button" class="btn btn-success" style="text-align:center;" onClick="bajarArchivo();"> Bajar Pedidos BOT&nbsp;
        <i class="fa fa-whatsapp"></i></button>

    </div>
	<br>
	  <div  style="text-align:center;">
        <button id="btnBajar" type="button" class="btn btn-info" style="text-align:center;" onClick="aExcel();">Bajar Pedidos Excel
        <i class="fa fa-file-excel-o"></i></button>

    </div>

</body>
</html>


<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>

<script type="text/javascript">


    function bajarArchivo() {
        
        fetch('http://www.axbot.com.ar/<?php  echo explode("/", $_SERVER["REQUEST_URI"])[1];  ?>/chatbot/api/botPedidos.php')
            .then((respuesta) => {
                return respuesta.json();
            }).then((respuesta) => {
                ListClientes = respuesta;

                if (ListClientes.length == 0) {
                    alert("No hay pedidos por bajar")
                } else {  

                    responseString = "888" + '\r\n';
                    ListClientes.forEach(function (item, index) {

                        isTheLastItem = ListClientes.length == index + 1;
                        fehafin = item.fechaEnviado.substring(6, 10) + "-" + item.fechaEnviado.substring(3, 5) + "-" + item.fechaEnviado.substring(0, 2) + " " + item.fechaEnviado.substring(11, 20)

                        if (isTheLastItem) {
                            lineaPedido = (item.clienteId + "," + fehafin + "," + item.producto + "," + item.cantidad + ",,,,,,,,,0");
                        }
                        else {
                            lineaPedido = (item.clienteId + "," + fehafin + "," + item.producto + "," + item.cantidad + ",,,,,,,,,0") + '\r\n';
                        }
                        responseString += lineaPedido   

                    });
                    downloadFile(responseString, "Pedidos_888.txt")
                }

            });

    }


    function downloadFile(data, fileName, type = "text/plain") {

        const a = document.createElement("a");
        a.style.display = "none";
        document.body.appendChild(a);
        a.href = window.URL.createObjectURL(
            new Blob([data], { type })
        );
        a.setAttribute("download", fileName);
        a.click();
        window.URL.revokeObjectURL(a.href);
        document.body.removeChild(a);
    }

function aExcel(){


location.href = "../sincronizar/B_pedidosExcel.php";
}


</script>