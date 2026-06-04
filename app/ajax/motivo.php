<?php
require_once '../config/auth.php';
require_once "../modelos/Motivo.php";

$motivoReclamo = new Motivo();


$id = isset($_POST["id"]) ? limpiarCadena($_POST["id"]) : "";
$codigo = isset($_POST["codigo"]) ? limpiarCadena($_POST["codigo"]) : "";
$motivo = isset($_POST["motivo"]) ? limpiarCadena($_POST["motivo"]) : "";
$idarea = isset($_POST["idarea"]) ? limpiarCadena($_POST["idarea"]) : "";
$ir = "5"; // "3";
$guardar = "false"; //"true";
if (strcasecmp($motivo, "Otros") == 0) {
    $ir = "5";
    $guardar = "false";
}
if (strcasecmp($motivo, "Finalizar") == 0) {
    $ir = "2";
    $guardar = "false";
}

//$codigo,$motivo,$idarea,$ir,$guardar

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp', 'selectArea']);

switch ($_GET["op"]) {
    case 'guardaryeditar':
        //verificacion del codigo ingresado en el formulario de alta/modificacion
        $select = " count(*) as cant ";
        $where = " opcionId ='$codigo' ";
        if (!empty($id)){
            $where .= " and id!=$id ";
        }
        $resultado = $motivoReclamo->filterMotivos($select, $where);
        $registro = $resultado->fetch_object();
        if ($registro->cant > 0){
            echo "Ya existe un motivo de reclamo con el codigo <strong>".strtoupper($codigo)."</strong>";
            break;
        }


//        $select = " count(*) as cant ";
//        $where = " area ='$idarea' ";
//        if (!empty($id)){
//            $where .= " and id!=$id ";
//        }
//        $resultado = $motivoReclamo->filterMotivos($select, $where);
//        $registro = $resultado->fetch_object();
//        if ($registro->cant > 0){
//            echo "Ya existe un motivo de reclamo con el mismo sector";
//            break;
//        }
        
        if (empty($id)) {
            $rspta = $motivoReclamo->insertar(strtoupper($codigo), $motivo, $idarea, $ir, $guardar);
            echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
        } else {
            $rspta = $motivoReclamo->editar($id, strtoupper($codigo), $motivo, $idarea, $ir, $guardar);
            echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
        }
        break;

    case 'eliminar':
        $rspta = $motivoReclamo->eliminar($id);
        echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
        break;

    case 'mostrar':
        $rspta = $motivoReclamo->mostrar($id);
        echo json_encode($rspta);
        break;

    case 'listarp':
        $rspta = $motivoReclamo->listarp();
        $data = Array();

        while ($reg = $rspta->fetch_object()) {
            $data[] = array(
                "0" => '<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar(\'' . $reg->id . '\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>' . ' ' . '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\'' . $reg->id . '\')"><i class="mdi mdi-delete m-n2"></i></button>',
                "1" => $reg->opcionId,
                "2" => $reg->opcion,
                "3" => $reg->area,

            );
        }
        $results = array(
            "draw" => 1,//info para datatables
            "recordsTotal" => count($data),//enviamos el total de registros al datatable
            "recordsFiltered" => count($data),//enviamos el total de registros a visualizar
            "data" => ($data));
        echo json_encode($results);
        break;

    case 'selectArea':
        require_once "../modelos/Area.php";
        $area = new Area();
        $rspta = $area->select();
        $values = '<option value="">--Seleccionar--</option>';
        while ($reg = $rspta->fetch_object()) {
            $values .= '<option value=' . $reg->id . '>' . $reg->area . '</option>';
        }
        echo $values;
        break;

}
?>