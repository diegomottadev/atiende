<?php
require_once '../config/auth.php';
require_once "../modelos/MotivoConsulta.php";

$motivoConsulta = new MotivoConsulta();

$id = isset($_POST["id"]) ? limpiarCadena($_POST["id"]) : "";
$codigo = isset($_POST["codigo"]) ? limpiarCadena($_POST["codigo"]) : "";
$motivo = isset($_POST["motivo"]) ? limpiarCadena($_POST["motivo"]) : "";
$idarea = isset($_POST["idarea"]) ? limpiarCadena($_POST["idarea"]) : "";
$ir = "3";
$guardar = "true";
if (strcasecmp($motivo, "Otros") == 0) {
    $ir = "5";
    $guardar = "false";
}
if (strcasecmp($motivo, "Finalizar") == 0) {
    $ir = "2";
    $guardar = "false";
}

$op = $_GET['op'] ?? '';
csrfGuard($op, ['mostrar', 'listarp', 'selectArea']);

switch ($_GET["op"]) {
    case 'guardaryeditar':
        if (empty($id)) {

            $select = " count(*) as cant ";
            $where = " opcionId ='$codigo' ";
            if (!empty($id)){
                $where .= " and id!=$id ";
            }
            $resultado = $motivoConsulta->filters($select, $where);
            $registro = $resultado->fetch_object();
            if ($registro->cant > 0){
                echo "Ya existe un motivo de consulta con el codigo <strong>".strtoupper($codigo)."</strong>";
                break;
            }

//            $select = " count(*) as cant ";
//            $where = " area ='$idarea' ";
//            if (!empty($id)){
//                $where .= " and id!=$id ";
//            }
//            $resultado = $motivoConsulta->filters($select, $where);
//            $registro = $resultado->fetch_object();
//            if ($registro->cant > 0){
//                echo "Ya existe un motivo de consulta con el mismo sector";
//                break;
//            }

            $rspta = $motivoConsulta->insertar($codigo, $motivo, $idarea, 15, 0);
            echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
        } else {
            $rspta = $motivoConsulta->editar($id, $codigo, $motivo, $idarea, 15,0);
            echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
        }
        break;


    case 'eliminar':
        $rspta = $motivoConsulta->eliminar($id);
        echo $rspta ? "Datos eliminados correctamente" : "No se pudo eliminar los datos";
        break;

    case 'mostrar':
        $rspta = $motivoConsulta->mostrar($id);
        echo json_encode($rspta);
        break;

    case 'listarp':
        $rspta = $motivoConsulta->listarp();
        $data = Array();
        ///SELECT `opcionId`, `opcion`, `menuId`, `guardar`, `area` FROM `menuitem` WHERE 1
        while ($reg = $rspta->fetch_object()) {
            $data[] = array(
                "0" => '<button class="btn btn-warning btn-sm btn-icon-line" onclick="mostrar(\'' . $reg->id . '\')"><i class="mdi mdi-lead-pencil m-n2"></i></button>' . ' ' . '<button class="btn btn-danger btn-sm btn-icon-line" onclick="eliminar(\'' . $reg->id . '\')"><i class="mdi mdi-delete m-n2"></i></button>',
                "1" => $reg->opcionId,
                "2" => $reg->opcion,
                "3" => $reg->area,

            );
        }
        $results = array(
            "sEcho" => 1,//info para datatables
            "iTotalRecords" => count($data),//enviamos el total de registros al datatable
            "iTotalDisplayRecords" => count($data),//enviamos el total de registros a visualizar
            "aaData" => $data);
        echo json_encode($results);
        break;

    case 'selectArea':
        require_once "../modelos/AreaConsulta.php";
        $area = new AreaConsulta();

        $rspta = $area->select();
        $values = '<option value="">--Seleccionar--</option>';
        while ($reg = $rspta->fetch_object()) {
            $values .= '<option value=' . $reg->id . '>' . $reg->area . '</option>';
        }
        echo $values;

        break;

}
?>