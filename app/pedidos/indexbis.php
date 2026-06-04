<!DOCTYPE html>


<html lang="es">
<head>

    <meta http-equiv="Expires" content="0">
    <meta http-equiv="Last-Modified" content="0">
    <meta http-equiv="Cache-Control" content="no-cache, mustrevalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <title>Pedidos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>


    <script src="../vistas/scripts/webSocketWbus.js?t=<?php echo time(); ?>"></script>

    <style type="text/css">
        <!--

        img {
            max-width: 200 px !important;
            max-height: 155px !important;
            padding:5px !important;
        }

        body {
            /*background-image: url("wap.jpg");
             background-repeat: repeat;*/
            font-family: 'tahoma';       /* Otro ejemplo */
            font-size: 16px;
        }

        .cargando {
            width: 100%;height: 100%;
            overflow: hidden;
            top: 0px;
            left: 0px;
            z-index: 10000;
            text-align: center;
            position:absolute;
            background-color: #FFFFFF;
            opacity:0.6;
            filter:alpha(opacity=40);

        }

        .amarillo {
            color: #E2CA00;
        }

        body{
            padding-bottom: 70px;

        }
        .Estilo1 {color: #FFFFFF}

        .colorRubro {
            color: #0B55C4;
            font-weight: bold;
        }
        table.reclamo {
            width: 100%;
            border-spacing: 1px;
            background-color: #ffffff;
            color: #666;
            border:#F0F0F0 1px solid;
        }
        table.reclamo tr {
            margin: 0px;
            padding-top:4px;
            padding-bottom:5px;

        }

        .boton_menos{
            padding-right:4px;
        }
        .boton_mas{
            padding-left:4px;
            padding-left:4px;
        }

        .row_td{
            padding-bottom:4px;

        }

        .Estilo2 {
            color: #3cd4ac;
            font-weight: bold;
        }

        .Estilo3 {font-size: 12px;}

        .loader {
            position: fixed;
            left: 0px;
            top: 0px;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background: url('loading.gif') 50% 50% no-repeat rgb(249,249,249);
            opacity: .8;
        }


        .btn-success {
            color: #fff;
            background-color: #3cd4ac;
            border-color: #3cd4ac;
        }

        .btn-success:hover {
            color: #fff;
            background-color: #3AEDCB;
            border-color: #3AEDCB;
        }

        .btn-info:hover {
            color: #fff;
            background-color: #EFBDCB;
            border-color: #EFBDCB;
        }

        .btn-info {
            color: #fff;
            background-color: #f6aabf;
            border-color: #f6aabf;
        }

        .btn-primary:hover {
            color: #fff;
            background-color: #9ea3a7 !important;
            border-color: #9ea3a7 !important;
        }

        .btn-primary {
            color: #fff;
            background-color: #9ea3a7 !important;
            border-color: #9ea3a7 !important;
        }


        .btn-warning:hover {
            color: #fff;
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }

        .btn-warning {
            color: #fff;
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }


        .navbarp{
            background-color: #9A7BFB   !important;
        }

    </style>
    <?php
    define('__ROOT__', dirname(dirname(__FILE__)));
    require (__ROOT__.'/config/global.php');
    ?>
    <script>

        $(window).on('load', function(){
            $(".loader").fadeOut("slow");
        });


        var intervalo;
        runCargar();

        function runCargar() {
            intervalo = setInterval(terminaCarga, 200);
        }

        function terminaCarga() {

            if ( document.getElementById( "tablapedido" )) {

                clearInterval(intervalo);

                document.getElementById('loader').style.display='none';

                var idPedido = localStorage.getItem("idPedido");
                if(idPedido==null){
                    localStorage.setItem("idPedido",<?php echo $_GET["ped"]; ?> );
                }else{

                    if(<?php echo $_GET["ped"] ?> ==localStorage.getItem("idPedido" )){


                        if(localStorage.getItem("pedido" )!=null){
                            pedido= stringToArray( localStorage.getItem("pedido" ));
                            for(var x=0;x<pedido.length-1;x++){
                                //console.log(pedido[x][0]+">"+pedido[x][2]);
                                if(pedido[x][0]!=".001")
                                    document.getElementById(''+pedido[x][0]).value =pedido[x][2] ;
                            }
                            listarPedidos();
                        }

                    }else{
                        localStorage.setItem("idPedido",<?php echo $_GET["ped"]; ?> );
                    }

                }


                console.log("Listo");
            }
        }

        function arrayToString(a){
            var r="";

            for(var i=0; i<a.length; i++){
                r+= a[i].join()+";";
            }

            return  r ;
        }

        function stringToArray(s){
            var array=new Array();
            var a =s.split(";");
            for(var i=0; i<a.length; i++){
                array.push(a[i].split(","));
            }
            return  array ;
        }

        var busquedaFlag= false;

        function NoBack(){
            history.go(1);
        }

        function busqueda(){
            document.getElementById('txBuscar').value ="";
            if(busquedaFlag){
                document.getElementById('rubro').style.display='block';

                document.getElementById('txBuscar').style. display='none';
                document.getElementById("icoFiltro").className = "glyphicon glyphicon-filter";
                listarRubro();
                busquedaFlag= false;

            }else{
                document.getElementById('rubro').style.display='none';
                document.getElementById('txBuscar').style. display='block';
                document.getElementById("icoFiltro").className = "glyphicon glyphicon-search";
                busquedaFlag= true;
            }
        }

        var codigo=null;
        var descripcion= null;
        var precio= null;
        var rubro=null;
        var promo=null;


        var totales=null;
        var pedido= new Array();
        var anclaje="";
        var telefono="";
        var token="";
        var nombre="";
        var ped="";
        var clienteId="";



        function MaysPrimera(string){
            return string.charAt(0).toUpperCase() + string.slice(1);
        }






        function verImagen(valor,desripcion,promo){

            $("#_producto").html("("+valor+")"+desripcion);
//$("#_promo").html(promo+" OFF");
            $("#myModal").modal();

            $("#imgAmplia").html("  <img src='../files/articulos/"+valor+".jpg' width='auto' onerror=\"this.src='../files/articulos/camara.jpg';\" >");

            var array = new Array();
            if(getCookie("favorito")!=null)
                var array= getCookie("favorito").split(",");

            var _favorito="<a  onClick='favorito(\""+valor+"\")'  ><img src='img/estrella.png' id='img"+valor+"' > Agregar a favorito</a>";
            if(array!=null)
                if(array.indexOf(valor)!=-1)
                    _favorito="<a  onClick='favorito(\""+valor+"\")' ><img src='img/estrella_ok.png' id='img"+valor+"' > Agregar a favorito</a>";

            $("#_favoritos").html(_favorito);


        }


        function filtroFavorito(){


            document.getElementById("rubro").selectedIndex=-1;

            tableProductos = document.getElementById("tbProductos");
            var rowCount = tableProductos.rows.length;
            for (var j=0; j<rowCount; j++) {
                // console.log(tableProductos.rows[j].cells[0].id));
                if((tableProductos.rows[j].cells[0].id).includes("f"))
                    tableProductos.rows[j].style.display = "";
                else
                    tableProductos.rows[j].style.display = "none";
            }
            var windowHeight = $(window).scrollTop();
            document.documentElement.scrollTop =- 180;

        }

        function irProductos(){
            $('.nav-tabs a[href="#home"]').tab('show');
        }

        function favorito(codigo){
            //var imagen = document.getElementById("img"+codigo);


            var image = document.getElementById('img'+codigo);
            var image2 = document.getElementById('es'+codigo);

            if (image.src.match("ok")) {
                image.src = "img/estrella.png";
                image2.src = "img/_estrella.png";
                var favorito=getCookie("favorito").replace(codigo+",", "");
                document.cookie ="favorito="+favorito;
                document.getElementById("f"+codigo).id= "c"+codigo;
            } else {
                image.src = "img/estrella_ok.png";
                image2.src = "img/_estrella_ok.png";
                var favorito=getCookie("favorito")+codigo+",";
                document.cookie ="favorito="+favorito;
                document.getElementById("c"+codigo).id= "f"+codigo;
            }

            var op = document.getElementById("rubro");

            if(op.options[0].value!="FAVORITOS"){
                var option = document.createElement("option");
                option.text = "FAVORITOS";
                op.add(option, op[0]);
            }
        }

        function getCookie(sKey) {
            if (!sKey) { return null; }
            return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
        }

        function cerrar() {
            var win = window.open("about:blank", "_self");
//   var win =window.open('','_parent','');
            setTimeout(function(){ win.close(); }, 1000);

        }

        //$('ul li:eq(0) a').text('Your Text')
        //localStorage.setItem("favorito","");
        function verHistorico(){

            /*
            $.get('http://www.axum.com.ar/puertadelsur/ControllerJME.aspx?&cmd=B_Historicos&Cliente='+clienteId).done(function (data) {
                console.log(data);
            });

            */

        }
        function listarRubro() {
            var str = document.getElementById("rubro").value;

            /*  var option = document.createElement("option");
            option.text = "Kiwi";
            x.add(option, x[0]);*/

            if(str!='FAVORITOS'){
                tableProductos = document.getElementById("tbProductos");
                var rowCount = tableProductos.rows.length;
                for (var j=0; j<rowCount; j++) {
                    //console.log((j+""+str));
                    if(tableProductos.rows[j].id != (j+""+str))
                        tableProductos.rows[j].style.display = "none";
                    else
                        tableProductos.rows[j].style.display = "";
                }
            }
            if(str=='FAVORITOS'){
                tableProductos = document.getElementById("tbProductos");
                var rowCount = tableProductos.rows.length;
                for (var j=0; j<rowCount; j++) {
                    // console.log(tableProductos.rows[j].cells[0].id));
                    if((tableProductos.rows[j].cells[0].id).match("f"))
                        tableProductos.rows[j].style.display = "";
                    else
                        tableProductos.rows[j].style.display = "none";
                }

            }
            /*
            if(str=='OFERTAS'){
                  tableProductos = document.getElementById("tbProductos");
                    var rowCount = tableProductos.rows.length;
                for (var j=0; j<rowCount; j++) {
                    // console.log(tableProductos.rows[j].cells[0].id));
                  if((tableProductos.rows[j].cells[0].id).match("o"))
                    tableProductos.rows[j].style.display = "";
                    else
                     tableProductos.rows[j].style.display = "none";
                }

            }
            */
            var windowHeight = $(window).scrollTop();


            document.documentElement.scrollTop =- 180;

        }
        function listarProductos() {

            var str = document.getElementById("txBuscar").value;

            tableProductos = document.getElementById("tbProductos");

            var rowCount = tableProductos.rows.length;
            for (var j=0; j<rowCount; j++) {
                // console.log(tableProductos.rows[j].className);
                var productoStr=tableProductos.rows[j].className;
                if(productoStr.toUpperCase().indexOf(str.toUpperCase())==-1)
                    tableProductos.rows[j].style.display = "none";
                else
                    tableProductos.rows[j].style.display = "";
            }


            var windowHeight = $(window).scrollTop();
            document.documentElement.scrollTop =- 180;

        }


        function irComentario(elmnt,valor,desc){

            if(parseInt(document.getElementById(elmnt).value)>0){
                var index=buscarCodigo(pedido,codigo[ parseInt(valor)]);
                document.getElementById("comment").value = pedido[index][6];
                document.getElementById("_desc").innerHTML  =desc ;
                document.getElementById("_codigo").value =elmnt ;
                document.getElementById("_fila").value =valor ;

                $("#Mcomentario").modal();
            }
        }
        function irComentario2(){

            $("#Mcomentario2").modal();

        }

        function agregarComentario2(){

            var obs=document.getElementById("_obs").value;
            console.log(obs);
            if(obs.length>0){

                var index = buscarCodigo(pedido,'.001');
                if(index>=0){

                    pedido[index][1]=obs;

                }else{
                    var articulos= new Array(7);
                    articulos[0]=".001";
                    articulos[1]=obs;
                    articulos[2]="1";
                    articulos[3]="0";
                    articulos[4]="0";
                    articulos[5]=clienteId;
                    articulos[6]="";
                    pedido.push(articulos);
                    //console.log(articulos+" OK");
                }
            }
            var table = document.getElementById("tablapedido");
            var rowCount = table.rows.length;

            for (var x=rowCount-1; x>0; x--) {
                table.deleteRow(x);
            }
            localStorage.setItem("pedido",arrayToString(pedido) );
            listarPedidos();

        }




        function agregarComentario(){


            var index=buscarCodigo(pedido,codigo[ parseInt(document.getElementById("_fila").value)]);
            var fila= parseInt(document.getElementById("_fila").value);
            if(index>=0){

                pedido[index][6]=document.getElementById("comment").value;

                //alert (pedido.length);
            }
            var table = document.getElementById("tablapedido");
            var rowCount = table.rows.length;

            for (var x=rowCount-1; x>0; x--) {
                table.deleteRow(x);
            }
            localStorage.setItem("pedido",arrayToString(pedido) );
            listarPedidos();


///$("#Mcomentario").modal();

        }

        function  sumarCantidad(elmnt,valor){

            document.getElementById(elmnt).value=parseInt(document.getElementById(elmnt).value)+1;
            //alert (index);
            var fila= parseInt(valor);
            var table = document.getElementById("tablapedido");

            var cantidad = parseInt(document.getElementById(elmnt).value)
            ///var indice =table.rows.length-1;
            var index=buscarCodigo(pedido,codigo[fila]);


            if(index>=0){

                var s=pedido[index][6];
                pedido[index] = new Array(7);
                pedido[index][0]=codigo[fila];
                pedido[index][1]=descripcion[fila].toLowerCase();
                pedido[index][2]=cantidad+"";
                pedido[index][3]= parseFloat(parseFloat(cantidad)* parseFloat(precio[fila])).toFixed( 2 );
                pedido[index][4]=precio[fila];
                pedido[index][5]=clienteId;
                pedido[index][6]=s;
                //alert (JSON.stringify(pedido[index]));
                //alert (pedido.length);
            }else{

                var articulos= new Array(7);
                articulos[0]=codigo[fila];
                articulos[1]=descripcion[fila].toLowerCase();
                articulos[2]=cantidad+"";
                articulos[3]= parseFloat(parseFloat(cantidad)* parseFloat(precio[fila])).toFixed( 2 );
                articulos[4]=precio[fila];
                articulos[5]=clienteId;
                articulos[6]="";
                pedido.push(articulos);
            }

            var rowCount = table.rows.length;

            for (var x=rowCount-1; x>0; x--) {
                table.deleteRow(x);
            }
            localStorage.setItem("pedido",arrayToString(pedido) );
            listarPedidos();

        }
        function irProducto(valor){
//alert (valor);
            anclaje=valor;
            $('.nav-tabs a[href="#home"]').tab('show');
//alert(valor)


        }

        function eliminarPedido(valor,valor1){

            var opcion = confirm("DESEA ELIMINAR: "+valor1+" ?");
            if (opcion === true) {

                var table = document.getElementById("tablapedido");
                var index=buscarCodigo(pedido,valor);

                if(index>0)pedido.splice(index,index);
                else
                    pedido= new Array();

                var rowCount = table.rows.length;

                for (var x=rowCount-1; x>0; x--) {
                    table.deleteRow(x);
                }

                localStorage.setItem("pedido",arrayToString(pedido) );
                listarPedidos();
                /*
                document.getElementById(valor).value=0;

                var rowCount = table.rows.length;
               pedido[index][3]="0";
            for (var x=rowCount-1; x>0; x--) {
               table.deleteRow(x);
            }
                for(var x=0;x<pedido.length;x++){
                       if(pedido[x][3]>0){
                          var row = table.insertRow(-1);
                          //var cell1 = row.insertCell(0);
                          var cell2 = row.insertCell(0);
                          var cell3 = row.insertCell(1);
                          var cell4 = row.insertCell(2);
                          var cell5 = row.insertCell(3);

                         // cell1.innerHTML = pedido[x][0];
                          cell2.innerHTML = "<a href='#' onClick='irProducto(\""+pedido[x][0]+"\")' > "+pedido[x][1]+"</a>";
                          cell3.innerHTML = pedido[x][2];
                          cell4.innerHTML = "$"+pedido[x][3];
                          cell5.innerHTML = " <a href='#' onClick='eliminarPedido(\""+pedido[x][0]+"\",\""+pedido[x][1]+"\")'> <span class='	glyphicon glyphicon-trash'></span> </a>";
                          }else{
                           pedido.splice(x, 1);
                           x--;
                          }
                        */
                //}

//localStorage.setItem("pedido",arrayToString(pedido) );

                $('.nav-tabs a[href="#menu1"]').html('<span class="glyphicon glyphicon-shopping-cart"></span> $'+getTotales(pedido));

            }

        }


        function buscarCodigo(matriz, valor) {
            var resultado=-1;
            for (var i = 0; i < matriz.length; i++) {
                if(matriz[i][0]==valor){
                    resultado=i;
                    break;
                }
            }
            return resultado;   // The function returns the product of p1 and p2
        }

        function getTotales(matriz) {
            var total=0.00;
            for (var i = 0; i < matriz.length; i++) {

                total+= parseFloat(matriz[i][3]);
            }
            ///alert (total.toFixed( 2 )+"");
            return total.toFixed( 2 );

        }


        function  restarCantidad(elmnt,valor){

            if(parseInt(document.getElementById(elmnt).value)>0){

                document.getElementById(elmnt).value=parseInt(document.getElementById(elmnt).value)-1;
                var fila= parseInt(valor);
                var table = document.getElementById("tablapedido");

                var cantidad = parseInt(document.getElementById(elmnt).value)
                ///var indice =table.rows.length-1;
                var index=buscarCodigo(pedido,codigo[fila]);


                if(index>=0){
                    var s=pedido[index][7];
                    if ( s == null ) s="";
                    //table.deleteRow(index+1);
                    pedido[index] = new Array(7);
                    pedido[index][0]=codigo[fila];
                    pedido[index][1]=descripcion[fila].toLowerCase();
                    pedido[index][2]=cantidad+"";
                    pedido[index][3]= parseFloat(parseFloat(cantidad)* parseFloat(precio[fila])).toFixed( 2 );
                    pedido[index][4]=precio[fila];
                    pedido[index][5]=clienteId;
                    pedido[index][6]=s;

                    //alert (JSON.stringify(pedido[index]));
                }else{

                    var articulos= new Array(7);
                    articulos[0]=codigo[fila];
                    articulos[1]=descripcion[fila].toLowerCase();
                    articulos[2]=cantidad+"";
                    articulos[3]= parseFloat(parseFloat(cantidad)* parseFloat(precio[fila])).toFixed( 2 );
                    articulos[4]=precio[fila];
                    articulos[5]=clienteId;
                    articulos[6]="";
                    pedido.push(articulos);
                }

                var rowCount = table.rows.length;

                for (var x=rowCount-1; x>0; x--) {
                    table.deleteRow(x);
                }

                localStorage.setItem("pedido",arrayToString(pedido) );
                listarPedidos();


            }

        }


        function listarPedidos(){

            var table = document.getElementById("tablapedido");

            for(var x=0;x<pedido.length;x++){

                if(pedido[x][3]>0 || pedido[x][0]===".001"){
                    var comentario="";
                    if(pedido[x][6].length>0){
                        comentario="<a href='#' > <span  style='color:#6c757d !important;'  class='glyphicon glyphicon-comment'> </span> </a>";
                        var element = document.getElementById("bt"+pedido[x][0]);
                        element.classList.remove("btn-primary");
                        element.classList.add("btn-warning");
                    }

                    var row = table.insertRow(-1);
                    //  var cell1 = row.insertCell(0);
                    var cell2 = row.insertCell(0);
                    var cell3 = row.insertCell(1);
                    var cell4 = row.insertCell(2);
                    var cell5 = row.insertCell(3);
                    //glyphicon glyphicon-comment
                    // cell1.innerHTML = pedido[x][0];
                    cell2.innerHTML =  comentario+"<a style='color:#6c757d !important;' href='#' onClick='irProducto(\""+pedido[x][0]+"\")' > "+pedido[x][1]+"</a>";
                    cell3.innerHTML = pedido[x][2];
                    cell4.innerHTML = "$"+pedido[x][3];
                    cell5.innerHTML = " <a  style='color:#6c757d !important;'  href='#' onClick='eliminarPedido(\""+pedido[x][0]+"\",\""+pedido[x][1]+"\")'> <span  style='color:#6c757d !important;'  class='glyphicon glyphicon-trash'></span> </a>";
                }else{
                    pedido.splice(x, 1);
                    x--;
                }
            }

            $('.nav-tabs a[href="#menu1"]').html('<span class="glyphicon glyphicon-shopping-cart"></span> $'+getTotales(pedido));

            var btAgregar = document.getElementById("btAgegra");
            var _div1 = document.getElementById("_div1");

            var tbTitulo = document.getElementById("tbTitulo");

            if(pedido.length>0){
                btAgregar.style.display = "none";
                _div1.style.display = '';
                tbTitulo.style.display = '';
            }
            else{
                btAgregar.style.display = "";
                tbTitulo.style.display ='none';
                _div1.style.display = 'none';
            }
        }


        function myFunction() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("myInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("myTable");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[0];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        function copia_portapapeles(){

            var textarea = document.getElementById("textarea");
            var miPedido="";
            for (var i = 0; i < pedido.length; i++) {
                //textarea.innerHTML = '';
                miPedido+=pedido[i][0]+"-"+pedido[i][1]+".."+pedido[i][2]+"\n";
                /// total+= parseFloat(pedido[i][3]);
            }
            textarea.innerHTML = miPedido;



            var answer = document.getElementById("copiar");
            textarea.select();
            try {
                // Copiando el texto seleccionado
                var successful = document.execCommand('copy');

                if(successful) answer.innerHTML = 'Pedido Copiado!';
                else answer.innerHTML = 'Incapaz de copiar!';
            } catch (err) {
                answer.innerHTML = 'Browser no soportado!';
            }
        }

        $(document).ready(function(){
            $(".nav-tabs a").click(function(){
                $(this).tab('show');
            });
            $('.nav-tabs a').on('shown.bs.tab', function(event){
                var x = $(event.target).text();         // active tab
                var y = $(event.relatedTarget).text();  // previous tab

                // alert("="+y);
                if(x!==" Productos"){
                    document.getElementById('idBuscar').style. display='none';
                    document.getElementById('idHistocico').style.display='block';
                    document.documentElement.scrollTop =-180;
                }

                if(x===" Productos"){



                    document.getElementById('idBuscar').style.display='block';
                    document.getElementById('idHistocico').style. display='none';
                    // listarRubro2("CERVEZA");
                    //$('html, body').animate({scrollTop:0}, 'slow');
                    document.location.href = "#"+anclaje;
                    //alert ($(window).scrollTop());
                    var windowHeight = $(window).scrollTop();
                    document.documentElement.scrollTop =windowHeight- 180;
                    //document.getElementById(anclaje).style.backgroundColor = '#FFC0CB';


                }

            });
        });




        function enviarPedido()
        {
            //pedido= new Array();
            if(getTotales(pedido)>1000){
                var opcion = confirm("Desea enviar el pedido ahora ?");
                if (opcion === true) {
                    document.getElementById('bloquea').style. display='block';
                    $('#btnEnviarPedidos').prop('disabled',true);
                    enviarPedidoSeleccionado();
                }
            }else{
                alert ("El importe minimo del pedido es de $1.000 !");
            }

            //document.getElementById("ejemplo").innerHTML = mensaje;
        }




        //-----------------------------------------------------------------------------------
        function enviarPedidoSeleccionado(){
            $('#btnEnviarPedidos').prop('disabled',true);

            var empresa = "<?php echo DB_NAME; ?>";
            var url = "<?php echo tenantUrl(); ?>";
            var vedid = "<?php echo $_GET['ved'] ?? null; ?>";
            console.log("vedid:", vedid);
            // var mensaje="<saludo> *"+nombre.split(" ")[0]+"*\nRecibimos un pedido por un total de *$"+getTotales(pedido)+"*\nConfirma este pedido?";
            // var mensaje = JSON.stringify(pedido);
            var json = {
                type: "_cliente_msg",
                token : token,
                telefono: telefono,
                nombre: nombre,
                ped:ped,
                total:getTotales(pedido),
                mensaje:pedido,
                ved: vedid
            };

            $.ajax({
                type: "POST",
                url: 'S_Pedidos_bis.php',
                data: "json="+JSON.stringify(json) ,
                success: function(data){
                    document.getElementById('bloquea').style. display='none';
                    // if(parseInt(data)>0){
                    //     var mensaje = "*" + nombre + "*, recibimos tu pedido \n";
                    //     mensaje += "*Pedido N°:* " + ped + "\n"
                    //     mensaje += "*Monto: $* " + getTotales(pedido) + "\n";
                    //     mensaje += "*Ticket:* 👇\n\n";
                    //     mensaje += `${url}/${empresa}/reportes/exTicket.php?id=${ped}\n\n`;
                    //     mensaje += "Para *pagar online* hace clic en el link: 👇\n\n";
                    //     mensaje += `${url}/` + empresa + "/pedidos/pago.php?ped=" + ped +
                    //         "&monto=" + getTotales(pedido) + "\n\n";
                    //     mensaje += "Si preferís otra opción de pago elige: 👇\n\n";
                    //     mensaje += "*A)* Pagar en efectivo en la entrega \n";
                    //     mensaje += "*B)* Transferencia bancaria \n";
                    //     mensaje += "*C)* Cuenta corriente \n";
                    //     mensaje += "*D)* Cancelar Pedido \n";
                    //
                    //
                    //     /**------------------------- */
                    //
                    //     var json2 = {
                    //         type: "_msg_externo",
                    //         empresa: token,
                    //         cmd:"chat",
                    //         msg: {
                    //             to :telefono,
                    //             custom_uid:  telefono + String(Math.random() * 999),
                    //             body:{
                    //                 text: mensaje
                    //             }
                    //         }
                    //     };
                    //
                    //     openWSConnection(ws, '8080', '/' + token + '/ws', JSON.stringify( json2));
                    //     //openWSConnection('localhost','8080','/'+token+'/ws',JSON.stringify(json2));
                    //     //
                    // }else{
                    //     alert("No se puede realizar el pedido en  este momento, comuniquese con el administrador");
                    // }
                    if(parseInt(data)>0){
                        // var mensaje ="*"+nombre+"* Recibimos su pedido \n";
                        // mensaje +="*Pedido N°:* "+ped+"\n"
                        // mensaje +="*Monto: $* "+getTotales(pedido)+"\n";
                        // mensaje +="*Costo de envio:$* 0.00 \n";
                        // mensaje +="Para finalizar por favor elija una opcion \n\n";
                        // mensaje+="*A)* Confirmar pedido. \n";
                        // mensaje+="*B)* Cancelar pedido. \n";
                        var mensaje ="*"+nombre+"* Su pedido a sido confirmado. \n";
                        /**------------------------- */
                        mensaje +="*Pedido N°:* "+ped+"\n";
                        mensaje +="*Monto: $* "+getTotales(pedido)+"\n";
                        mensaje +="*Costo de envio:$* 0.00 \n";
                        mensaje += "*Ticket:* 👇\n\n";
                        // mensaje += "http://www.atiende.lat/"+token+`/reportes/exTicket.php?id=${ped}\n\n`;
                        mensaje += url+`/reportes/exTicket.php?id=${ped}\n\n`;
                        var json2 = {
                            type: "_msg_externo",
                            empresa: token,
                            cmd:"chat",
                            msg: {
                                to :telefono,
                                custom_uid: telefono,
                                body:{
                                    text: mensaje
                                }
                            }
                        };


                        //Chequea si existe un vendedor
                        var messageClient = null;
                        var jsonToClient = {
                            type: "_msg_externo",
                            empresa: token,
                            cmd:"chat",
                            msg: {
                                to :"",
                                custom_uid: "",
                                body:{
                                    text: ""
                                }
                            }
                        };
                        if (vedid !== ""){

                            var json = {
                                type: "_cliente_msg",
                                token : token,
                                ped:ped,
                            };
                            //
                            $.ajax({
                                type: "POST",
                                url: 'R_Cliente.php',
                                data: "json=" + JSON.stringify(json),
                                success: function (data) {
                                    console.log(data);
                                    var messageClient ="*Su pedido a sido confirmado* \n";
                                    /**------------------------- */
                                    messageClient +="*Pedido N°:* "+ped+"\n";
                                    messageClient +="*Vendedor Tel:* "+telefono+"\n";
                                    messageClient +="*Vendedor:* "+nombre+"\n";
                                    messageClient +="*Monto: $* "+getTotales(pedido)+"\n";
                                    messageClient +="*Costo de envio:$* 0.00 \n";
                                    messageClient += "*Ticket:* 👇\n\n";
                                    messageClient += "http://www.atiende.lat/"+token+"/reportes/exTicket.php?id="+ped+"\n\n";
                                    jsonToClient.msg.to(data);
                                    jsonToClient.msg.custom_uid(data);
                                    jsonToClient.body.text(messageClient);
                                }
                            });
                            //
                            //
                        }

                        webSocketMain.onopen = function(openEvent) {
                            console.log("WebSocket OPEN: " + mensaje);

                            webSocketMain.onopen = function () {
                                webSocketMain.send(JSON.stringify( json2));
                                var timer1;
                                var timer2;
                                timer1 = setInterval(function() {
                                    if (webSocketMain.bufferedAmount === 0)
                                        if (vedid !== "") {
                                            webSocketMain.send(JSON.stringify(jsonToClient));
                                            setInterval(function() {
                                                if (webSocketMain.bufferedAmount === 0){
                                                    webSocketMain.close();
                                                    clearInterval(timer1);
                                                }
                                            }, 60);
                                        }else{
                                            webSocketMain.close();
                                            pedido = new Array();
                                            webSocketMain.close();
                                            location.href = "finaliza.php";
                                        }
                                }, 60);

                            };
                        };

                        //openWSConnection(ws, '8080', '/' + token + '/ws', JSON.stringify( json2));
                        //openWSConnection('localhost','8080','/'+token+'/ws',JSON.stringify(json2));

                    }else{
                        alert("Ocurrio un erro inesperado");
                    }
                }
            });


            //	 openWSConnection('54.175.225.1','8080','/wsbot/ws',JSON.stringify(json));



        }




        function openWSConnection(hostname, port, endpoint, mensaje ) {

            var webSocketURL = hostname + endpoint;

            console.log("openWSConnection::Connecting to: " + webSocketURL);
            try {
                webSocket = new WebSocket(webSocketURL);
                webSocket.onopen = function(openEvent) {
                    console.log("WebSocket OPEN: " + mensaje);
                    webSocket.send(mensaje);
                    pedido = new Array();
                    webSocket.close();
                    location.href = "finaliza.php";

                };

                webSocket.onclose = function (closeEvent) {
                    console.log("WebSocket CLOSE: " + JSON.stringify(closeEvent, null, 4));


                };
                webSocket.onerror = function (errorEvent) {
                    console.log("WebSocket ERROR: " + JSON.stringify(errorEvent, null, 4));
                };
                webSocket.onmessage = function (messageEvent) {
                    var wsMsg = messageEvent.data;

                };
            } catch (exception) {
                console.error(exception);
            }
        }

    </script>
</head>
<body OnLoad="NoBack();" >

<div class="loader" id="loader"></div>

<?php
include_once("../config/Connection.php");

$lista="lista1";
if (isset($_GET["ped"])) {
    $favorito_array= explode(",", $_COOKIE["favorito"]);
    $row = mysqli_fetch_array(Connection::runQuery("SELECT link_pedidos.*,link_pedidos.telefono as cel, clientes.* FROM `link_pedidos` inner join clientes on link_pedidos.clienteId=clientes.codigo where  link_pedidos.id  =  '".$_GET["ped"]."' and  link_pedidos.estado = 0  "));
//and link_pedidos.estado = 0 and DATE(link_pedidos.fecha) = NOW()
    if($row != NULL  ){

        $deposito=$row["deposito"];
        $lista="lista".$row["lista"];

        echo "<script> telefono ='".$row["cel"]."' ; token='".$row["token"]."'; nombre= '".$row["razonSocial"]."'; ped='".$_GET["ped"]."';clienteId='".$row["clienteId"]."'; </script>";

        ?>
        <nav class="navbar navbarp navbar-inverse navbar-fixed-top"  style="background-color:#3C8DBC"  >
            <div class="container-fluid">
                <ul class="nav navbar-nav">

                    <li class="Estilo1"><?php echo $row["razonSocial"]."<br>".$row["direccion"]; ?></li>

                </ul>

            </div>
        </nav>


        <nav class="navbar  navbar-default navbar-fixed-bottom" >
            <div class="container-fluid" >
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header" style="padding-top:6px; padding-left:10px;padding-right:10px;display:none;"  id="idBuscar">
                    <div class="input-group" >
                        <span class="input-group-addon"  onClick="busqueda()" >   <i class="glyphicon glyphicon-filter"  id="icoFiltro"></i></span>
                        <input type="text" class="form-control" id="txBuscar" style="display:none" onKeyUp="listarProductos()">
                        <?php
                        $result=Connection::runQuery("SELECT `linea`,`rubro` FROM `articulos` WHERE 1 GROUP BY rubro ORDER BY rubro ASC");
                        echo " <select class='form-control' id='rubro' onchange='listarRubro()' style='display:block;' >";

                        if(count($favorito_array)>1)
                            echo "<option value='FAVORITOS' >FAVORITOS</option>";

                        while($row=mysqli_fetch_array($result)){

                            echo "<option value='".str_replace(" ", "_", $row["rubro"])."' >".$row["rubro"]."</option>";

                        }

                        echo "</select>";


                        // SDK de Mercado Pago


                        ?>

                    </div>
                </div>
                <div class="navbar-header" style="padding-top:6px; padding-left:10px;padding-right:10px; display:block;"   id="idHistocico" align="right">

                    <button style="margin-left: -15px;" type="button" class="btn btn-success" id="btnEnviarPedidos" onClick="enviarPedido();">
                        <span class="glyphicon glyphicon-send"></span> Enviar pedido
                    </button>
                </div>

            </div>
        </nav>



        <div style="margin-top:80px;" id="contenedor">

            <ul class="nav nav-tabs navbar-fixed-top" style="margin-top:50px;background-color:#F0F0F0" >


                <li > <a href="#home"  data-toggle="tab"  class="Estilo2"><span class="glyphicon glyphicon-plus"></span> Productos</a></li>
                <li class="active"><a href="#menu1" data-toggle="tab" class="Estilo2"><span class="glyphicon glyphicon-shopping-cart"></span> $ 0.00</a></li>
            </ul>

            <div class="tab-content">
                <div id="home" class="tab-pane fade" >
                    <!-- TABLA DE PRODUCTOS -->
                    <!--
                      <label for="sel1">Selecione Rubro: </label>
                          <select class="form-control" id="sel1">
                            <option>CERVEZA</option>
                            <option>GASEOSA</option>
                            <option>AGUAS</option>
                            <option>BEBIDAS SABORIZADAS</option>
                          </select>

                    -->


                    <center> <table id="tbProductos"  width="auto">
                            <br>

                            <?php



                            $result=Connection::runQuery("SELECT * FROM `articulos` where 1 order by linea,rubro  ");
                            echo " <tbody> ";
                            $rubro=""; $codigo="";$descripcion="";$precio="";$rubro="";
                            $bandera=0;
                            $fila=0;
                            while($row=mysqli_fetch_array($result)){
                                $d = explode("|",$row["deposito"]);
                                if(strlen (array_search($deposito, $d))>0 ){

                                    // echo $deposito."". $row["deposito"];

                                    //****************************************
                                    $img_favorito="img/_estrella.png";
                                    $fav="c";
                                    if (in_array($row["codigo"], $favorito_array)) {
                                        $img_favorito="img/_estrella_ok.png";
                                        $fav="f";
                                    }
                                    if ($row["oferta"]=="1") {
                                        $fav="o";
                                    }
                                    //*************************************************

                                    echo "<tr  id='".$fila.str_replace(" ", "_", $row["rubro"])."' class='".$row["descripcion"]."' > <td   id='".$fav.$row["codigo"]."' >";

                                    $codigo.="'".$row["codigo"]."',";
                                    $descripcion.="'".$row["descripcion"]."',";
                                    $row[$lista] += ($row[$lista] * $row["iva"] / 100)+$row["impInt"];
                                    $precio.= "'".$row[$lista]."',";
                                    $rubro.= "'".$row["rubro"]."',";

                                    ?>



                                    <?php


                                    $imagen="";

                                    if (file_exists("../files/articulos/".$row["codigo"].".jpg")) {
                                        $imagen="../files/articulos/".$row["codigo"].".jpg?".date("YmdHis");
                                    } else{
                                        $imagen="../files/articulos/camara.jpg";
                                    }
                                    ?>


                                    <table width="100%" border="0"  class="reclamo" >
                                        <tr>
                                            <td width="12%" rowspan="3" style="position:relative;">
                                                <!--ICONO DE FAVIRITO  -->
                                                <?php


                                                ?>

                                                <img  onClick="verImagen('<?php echo $row["codigo"]; ?>','<?php echo $row["descripcion"]; ?>','<?php echo $row["detalle_oferta"]; ?>')" src="<?php echo $imagen; ?>" />
                                                <img id='es<?php echo $row["codigo"]; ?>' src="<?php echo $img_favorito;  ?>"  style="position:absolute;z-index:0;cursor:pointer;left:5px; top: 5px;">
                                                <?php if($row["oferta"]=="1") echo "<span class='label label-warning'  style='position:absolute;z-index:0;cursor:pointer;left:1px; top: 95px;'>".$row["detalle_oferta"]." OFF</span>"; ?>

                                            </td>
                                            <td align="center" style="padding-right:10px;"> <span class="Estilo3">  <?php echo ucwords(strtolower ($row["descripcion"])); ?></span> </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <!--   PRECIO Y FAVORITO -->
                                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>

                                                        <td align="center"> <strong>$<?php echo number_format($row[$lista], 2, '.', '')."  x ".$row["pack"]; ?></strong></td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="row_td" align="center">
                                                <table width="100%" border="0"   cellspacing="5" cellpadding="5">
                                                    <tr>
                                                        <td align="right" class="boton_menos"><button type="button" class="btn btn-info" onClick="restarCantidad('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?> )" > <span class="glyphicon glyphicon-minus"></span> </button>    </td>
                                                        <td align="center" width="12%" ><input class="form-control" type="text" id="<?php echo $row["codigo"];?>" value="0" disabled="disabled" style="text-align:center; width:60px;"> </td>
                                                        <td align="left" class="boton_mas"><button type="button" class="btn btn-success" onClick="sumarCantidad('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?> )" ><span class="glyphicon glyphicon-plus"></span> </button></td> </tr>
                                                    <!--------------------------------------------------->

                                                    <tr>
                                                        <td colspan="3" align="center"><button style="margin: 5px;" type="button" id="bt<?php echo $row["codigo"];?>"  class="btn btn-primary btn-xs" onClick="irComentario('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?> ,' <?php echo ucwords(strtolower ($row["descripcion"])); ?>')" > Agregar comentario </button>    </td>
                                                    </tr>
                                                    <!---->


                                                </table></td>
                                        </tr>
                                        <!---->

                                        <!---->
                                    </table>



                                    </td>  </tr>



                                    <?php
                                    $fila++;
                                }
                            }


                            //echo "la variable codigo esta ".$codigo;
                            echo "<script >  codigo=[".$codigo."];  descripcion =[".$descripcion."]; precio =[".$precio."];  rubro=[".$rubro."]; listarRubro(); </script>";

                            if (isset($_GET["promo"]))
                                echo "<script >   promo= ".$_GET["promo"]." </script>";
                            ?>

                            </tbody>
                        </table>

                        <!--FIN TABLA PRODUCTOS-->
                </div>
                <div id="menu1" class="tab-pane fade in active" >
                    <!--INICIO TABLA PEDIDO
                    onClick="enviarPedido();"
                    <div style="margin-left:-400px; margin-top:-32px;" >
                     <textarea id="textarea" rows="1" cols="20">
                    </textarea>
                    </div>
                    -->
                    <!--	<a href="#" class="navbar-fixed-top" style="margin-top:150px;" >Volver arriba</a>-->
                    <div style="margin-top:100px;"  >

                        <div style="padding:10px;" id="btAgegra">
                            <button type="button" class="btn btn-success btn-block"  onClick="irProductos()">
                                <span class="glyphicon glyphicon-plus"></span> Agregar productos
                            </button>
                        </div>

                        <table class="table table-condensed" id="tablapedido" >
                            <thead id="tbTitulo"  style="display:none" >
                            <tr>
                                <th>Producto</th>
                                <th>Cant</th>
                                <th>SubTotal</th>
                                <th>#</th>
                            </tr>
                            </thead  >

                            </tbody>
                        </table>

                        <div class="form-group" id="_div1">
                            <a style="margin-left: 10px;" class="btn btn-xs  btn-warning" href="#" onClick="irComentario2()">Agregar comentario <span class="glyphicon glyphicon-plus-sign"></span></a>
                            <!--  <textarea class="form-control" rows="2" id="_obs" disabled="disabled"></textarea>-->
                        </div>
                    </div>
                    <!--FIN DE TABLA PEDIDO-->
                </div>

            </div>
        </div>
        <?php


    }else{

        echo "<div class='alert alert-danger'>
  <strong>Error!</strong> La credencial no existe o esta vecida, <br> solicite nuevamente el link para hacer un pedido
</div>";
    }



}else{

    echo "<div class='alert alert-danger'>
  <strong>Error!</strong> La Aplicacion no esta disponible sin credencial
</div>";
}


?>

<div id="bloquea" class="cargando" style="display:none;">

    <img style="margin-left: 5%;margin-top: 15%" alt="Espere..." src="loading.gif" />
    <div align="center"><h3>Espere un momento...</h3></div>
</div>


<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h5 class="modal-title"><span id="_producto">  </span></h5>

            </div>
            <div class="modal-body" align="center">
                <div align="center">
                    <span id="imgAmplia"> </span>

                </div>
                <h3><span class="label label-warning" id="_promo"> </span> </h3>
            </div>
            <div class="modal-footer">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="left"> <span id="_favoritos"></span></td>
                        <td align="right"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></td>
                    </tr>
                </table>


            </div>
        </div>
    </div>
</div>
</div>



​  <!-- Modal -->
<div class="modal fade" id="Mcomentario" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Comentario</h4>
            </div>
            <div class="modal-body">
                <input name="_codigo" type="hidden" id="_codigo">
                <input name="_fila" type="hidden" id= "_fila" >
                <label  id="_desc" >    </label>


                <div class="form-group">
                    <label for="comment">Comentario:</label>
                    <textarea class="form-control" rows="5" id="comment" name="comment" ></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" onClick="agregarComentario()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!---->
​  <!-- Modal -->
<div class="modal fade" id="Mcomentario2" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Comentario</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="comment">Comentario:</label>
                    <textarea class="form-control" rows="5" id="_obs" name="_obs" ></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" onClick="agregarComentario2()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!---->

<?php


?>
<script>

    if(promo!=null){
        $('.nav-tabs a[href="#home"]').tab('show');
        document.getElementById("rubro").selectedIndex= document.getElementById("rubro").length-1;
        document.getElementById('idBuscar').style.display='block';
        document.getElementById('idHistocico').style. display='none';
        listarRubro();
        var windowHeight = $(window).scrollTop();
        document.documentElement.scrollTop =windowHeight- 180;
        // document.getElementById(anclaje).style.backgroundColor = '#FFC0CB';

    }


</script>

</body>
</html>
