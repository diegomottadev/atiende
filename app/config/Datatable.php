<?php
/**
 * Helper genérico para SERVER-SIDE PROCESSING de DataTables.
 *
 * Centraliza, una sola vez, todo lo común y delicado:
 *  - parseo de los params que manda DataTables (start/length/search/order/columns)
 *  - armado del WHERE/ORDER/LIMIT con PREPARED STATEMENTS (input del usuario nunca concatenado)
 *  - whitelist de columnas para orden y filtros (evita SQL injection por nombre de columna)
 *  - totales (recordsTotal / recordsFiltered) para la paginación
 *
 * Cada handler de ajax/ que liste una tabla grande le pasa su config (tabla, whitelist,
 * columnas de búsqueda) y decora las filas (botones de acción, imágenes, etc.).
 *
 * Escala a millones de filas: la DB hace el trabajo apoyada en índices; el navegador
 * recibe solo la página pedida.
 */
if (!class_exists('Datatable')) {
class Datatable {

    /**
     * @param mysqli $conexion    conexión activa (global $conexion del tenant)
     * @param string $tabla       nombre de la tabla
     * @param array  $colMap      [indiceColumnaDataTables => 'columna_real'] — whitelist orden/filtro
     * @param array  $searchCols  columnas donde aplica la búsqueda global (LIKE substring)
     * @param array  $opts        opcional:
     *                              'select'       => lista de columnas (default '*')
     *                              'fetch'        => 'num' (array posicional) | 'assoc' (default 'num')
     *                              'defaultOrder' => p.ej. '`codigo` DESC' (si no viene order del front)
     *                              'maxLength'    => tope de filas por página (default 500)
     *                              'request'      => array de params (default $_REQUEST)
     * @return array ['recordsTotal'=>int, 'recordsFiltered'=>int, 'rows'=>array]
     */
    public static function serverSide($conexion, $tabla, $colMap, $searchCols, $opts = array()) {
        $select       = isset($opts['select'])       ? $opts['select']       : '*';
        $fetch        = isset($opts['fetch'])        ? $opts['fetch']        : 'num';
        $defaultOrder = isset($opts['defaultOrder']) ? $opts['defaultOrder'] : null;
        $maxLength    = isset($opts['maxLength'])    ? (int)$opts['maxLength'] : 500;
        $req          = isset($opts['request'])      ? $opts['request']      : $_REQUEST;

        $start   = isset($req['start'])  ? (int)$req['start']  : 0;
        $length  = isset($req['length']) ? (int)$req['length'] : 10;
        $buscar  = isset($req['search']['value']) ? trim((string)$req['search']['value']) : '';
        $order   = (isset($req['order'])   && is_array($req['order']))   ? $req['order']   : array();
        $columns = (isset($req['columns']) && is_array($req['columns'])) ? $req['columns'] : array();

        // total sin filtrar
        $recordsTotal = 0;
        if ($r = $conexion->query("SELECT COUNT(*) c FROM `$tabla`")) {
            $row = $r->fetch_assoc(); $recordsTotal = (int)$row['c'];
        }

        $where = array(); $params = array(); $types = '';

        // Búsqueda global → substring (LIKE) en las columnas configuradas
        if ($buscar !== '' && $searchCols) {
            $ors = array();
            foreach ($searchCols as $c) { $ors[] = "`$c` LIKE ?"; $params[] = '%'.$buscar.'%'; $types .= 's'; }
            $where[] = '('.implode(' OR ', $ors).')';
        }

        // Filtros por columna → coincidencia exacta (solo columnas de la whitelist)
        if (is_array($columns)) {
            foreach ($columns as $idx => $col) {
                $idx = (int)$idx;
                if (!isset($colMap[$idx])) continue;
                $v = isset($col['search']['value']) ? trim((string)$col['search']['value']) : '';
                if ($v === '') continue;
                $v = preg_replace('/^\^(.*)\$$/', '$1', $v); // normalizar ^val$ → val
                $where[] = "`{$colMap[$idx]}` = ?"; $params[] = $v; $types .= 's';
            }
        }

        $whereSql = $where ? (' WHERE '.implode(' AND ', $where)) : '';

        // total filtrado
        $recordsFiltered = $recordsTotal;
        if ($whereSql !== '') {
            if ($stmt = $conexion->prepare("SELECT COUNT(*) c FROM `$tabla`$whereSql")) {
                if ($params) { $stmt->bind_param($types, ...$params); }
                $stmt->execute();
                $rf = $stmt->get_result()->fetch_assoc();
                $recordsFiltered = (int)$rf['c'];
                $stmt->close();
            }
        }

        // ORDER BY con whitelist (columna y dirección validadas)
        $orderSql = $defaultOrder ? (' ORDER BY '.$defaultOrder) : '';
        if (isset($order[0]['column'])) {
            $ci  = (int)$order[0]['column'];
            $dir = (strtolower(isset($order[0]['dir']) ? $order[0]['dir'] : '') === 'asc') ? 'ASC' : 'DESC';
            if (isset($colMap[$ci])) { $orderSql = " ORDER BY `{$colMap[$ci]}` $dir"; }
        }

        // LIMIT saneado
        $start  = max(0, $start);
        $length = (int)$length;
        if ($length <= 0)         { $length = 10; }        // -1 (=todos) acotado: nunca volcar millones
        if ($length > $maxLength) { $length = $maxLength; }

        $rows = array();
        if ($stmt = $conexion->prepare("SELECT $select FROM `$tabla`$whereSql$orderSql LIMIT ?, ?")) {
            $allTypes  = $types.'ii';
            $allParams = array_merge($params, array($start, $length));
            $stmt->bind_param($allTypes, ...$allParams);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($fetch === 'assoc') { while ($row = $res->fetch_assoc()) { $rows[] = $row; } }
            else                    { while ($row = $res->fetch_row())   { $rows[] = $row; } }
            $stmt->close();
        }

        return array('recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'rows' => $rows);
    }

    /** Valores distintos de una columna (para poblar dropdowns de filtro). Whitelist obligatoria. */
    public static function distinct($conexion, $tabla, $col, $colsPermitidas) {
        if (!in_array($col, $colsPermitidas, true)) return array();
        $out = array();
        $sql = "SELECT DISTINCT `$col` v FROM `$tabla` WHERE `$col` IS NOT NULL AND `$col` <> '' ORDER BY `$col` ASC";
        if ($r = $conexion->query($sql)) {
            while ($row = $r->fetch_assoc()) { $out[] = $row['v']; }
        }
        return $out;
    }
}
}
