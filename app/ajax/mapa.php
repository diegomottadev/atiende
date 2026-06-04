<?php
require_once '../config/auth.php';
include_once("../config/Connection.php");
//header('Content-Type: application/json');

switch ($_GET["op"]) {


    case 'ventas':
        $and = ""; 
        $data = array();
        if ($_POST["vendedor"]) //like
            $and .= "AND p.vendedor in (" . implode(",", $_POST['vendedor']) . ") ";
        if ($_POST["ramo"])
            $and .= "AND p.ramo in ('" . implode("','", $_POST["ramo"]) . "') ";
        if ($_POST["zona"])
            $and .= "AND p.zona in ('" . implode("','", $_POST["zona"]) . "') ";
        if ($_POST["repartidor"])
            $and .= "AND v.repartidor_id in (" . implode(",", $_POST["repartidor"]) . ") ";
       
        $sql = "SELECT  p.*, v.pedidoid,  ROUND(SUM(subtotal),0) as cantidad FROM pedidos v INNER JOIN clientes p ON v.clienteId=p.codigo WHERE   DATE(v.fecha)>='" . $_POST["fecha_inicio"] . "' AND DATE(v.fecha)<='" . $_POST["fecha_fin"] . "'  $and GROUP BY `clienteId` order by cantidad desc ";
        $query = Connection::runQuery($sql); 
        while ($row = mysqli_fetch_assoc($query)) {
            $row["razonSocial"] = ucfirst(strtolower($row["razonSocial"]));
            $row["total_format"] = number_format($row["cantidad"], 0, "", ".");
            $data[] = $row;
        }
        echo json_encode($data);
        break;

    case 'reclamos':

        $and = "";
        $data = array();
        if ($_POST["vendedor"])
            $and .= "AND p.vendedor in (" . implode(",", $_POST['vendedor']) . ") ";
        if ($_POST["ramo"])
            $and .= "AND p.ramo in ('" . implode("','", $_POST["ramo"]) . "') ";
        if ($_POST["zona"])
            $and .= "AND p.zona in ('" . implode("','", $_POST["zona"]) . "') ";
        
        $sql = "SELECT r.clienteId,p.*, COUNT(r.`reclamoId`) as cantidad  FROM reclamos r INNER JOIN clientes p ON r.clienteId= p.codigo WHERE  DATE(r.fecha_ingreso)>='" . $_POST["fecha_inicio"] . "' AND DATE(r.fecha_ingreso)<='" . $_POST["fecha_fin"] . "'  $and GROUP BY r.clienteId ORDER BY cantidad DESC";
        $query = Connection::runQuery($sql);

        while ($row = mysqli_fetch_assoc($query)) {
            $row["razonSocial"] = ucfirst(strtolower($row["razonSocial"]));
            $row["total_format"] = number_format($row["cantidad"], 0, "", ".");
            $data[] = $row;
        }
        echo json_encode($data);

        break;

    case 'grafico_ventas':
        $and = "";
        $data = array();
        if ($_POST["vendedor"])
            $and .= "AND p.vendedor like '" . $_POST["vendedor"] . "' ";
        if ($_POST["ramo"])
            $and .= "AND p.ramo like '" . $_POST["ramo"] . "' ";
        if ($_POST["zona"])
            $and .= "AND p.zona like '" . $_POST["zona"] . "' ";

        $query = Connection::runQuery("SELECT  DATE_FORMAT( v.fecha, '%M') AS fecha, ROUND(SUM(v.subtotal),2) as cantidad FROM pedidos v INNER JOIN clientes p ON v.clienteId=p.codigo WHERE DATE(v.fecha)>='" . $_POST["fecha_inicio"] . "' AND DATE(v.fecha)<='" . $_POST["fecha_fin"] . "'  $and GROUP BY DATE_FORMAT( v.fecha, '%M')   ");

        while ($row = mysqli_fetch_assoc($query)) {
            $periodo[] = $row["fecha"];
            $cantidad[] = $row["cantidad"];

        }
        $data[] = $periodo;
        $data[] = $cantidad;
        echo json_encode($data);

        break;
    case 'grafico_reclamos':

        $and = "";
        $data = array();
        if ($_POST["vendedor"])
            $and .= "AND p.vendedor like '" . $_POST["vendedor"] . "' ";
        if ($_POST["ramo"])
            $and .= "AND p.ramo like '" . $_POST["ramo"] . "' ";
        if ($_POST["zona"])
            $and .= "AND p.zona like '" . $_POST["zona"] . "' ";

        $query = Connection::runQuery("SELECT DATE_FORMAT( r.fecha_ingreso, '%M') AS fecha, COUNT(r.`reclamoId`) as cantidad  FROM reclamos r INNER JOIN clientes p ON r.clienteId= p.codigo WHERE DATE(r.fecha_ingreso)>='" . $_POST["fecha_inicio"] . "' AND DATE(r.fecha_ingreso)<='" . $_POST["fecha_fin"] . "'  $and GROUP BY DATE_FORMAT( r.fecha_ingreso, '%M') ");
        while ($row = mysqli_fetch_assoc($query)) {
            $periodo[] = $row["fecha"];
            $cantidad[] = $row["cantidad"];
        }
        $data[] = $periodo;
        $data[] = $cantidad;
        echo json_encode($data);
        break;
}
?>