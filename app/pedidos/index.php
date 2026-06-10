<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once __ROOT__ . '/config/Connection.php';
// Tenant desde subdominio (Nginx HTTP_X_TENANT) o fallback a ?t= para compatibilidad
$tenantSlug = preg_replace('/[^a-z0-9_]/', '', strtolower($_SERVER['HTTP_X_TENANT'] ?? $_GET['t'] ?? ''));
if ($tenantSlug !== '') {
    Connection::setDatabase('atiende_' . $tenantSlug);
} elseif (isset($_GET['ped'])) {
    $_pedId = intval($_GET['ped']);
    try {
        $_ppPdo = new PDO('mysql:host=' . DB_HOST . ';port=3306;dbname=pedidos_platform;charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        foreach ($_ppPdo->query("SELECT db_name FROM tenants WHERE deleted_at IS NULL AND estado='activo'")->fetchAll(PDO::FETCH_COLUMN) as $_ppDB) {
            $_l = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, $_ppDB);
            if (!$_l) continue;
            $_r = mysqli_query($_l, "SELECT id FROM link_pedidos WHERE id=$_pedId LIMIT 1");
            if ($_r && mysqli_num_rows($_r) > 0) {
                Connection::setDatabase($_ppDB);
                if (strncmp($_ppDB, 'atiende_', 8) === 0) $tenantSlug = substr($_ppDB, 8);
                mysqli_close($_l); break;
            }
            mysqli_close($_l);
        }
        unset($_ppPdo, $_ppDB, $_l, $_r, $_pedId);
    } catch (Exception $_e) {}
}
require(__ROOT__ . '/config/global.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <title>Atiende | Pedidos</title>
    <link href="../public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="../public/bs5/bootstrap.min.css">
    <link rel="stylesheet" href="../public/assets/css/icons.min.css">
    <script src="../public/bs5/bootstrap.bundle.min.js"></script>
    <script src="../public/js/jquery.min.js"></script>
    <link rel="stylesheet" href="../public/sweetAlert2/sweetalert2.min.css">
    <script src="../public/sweetAlert2/sweetalert2.all.min.js"></script>
    <style>
        :root { --ap: #6c63ff; --am: #3cd4ac; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f2f5; padding-top: 108px; padding-bottom: 72px; }
        .navbar-atiende { background: var(--ap) !important; min-height: 56px; }
        .client-name { color: #fff; font-weight: 700; font-size: .95rem; line-height: 1.2; }
        .client-addr { color: rgba(255,255,255,.8); font-size: .75rem; }
        .tab-strip { position: fixed; top: 56px; left: 0; right: 0; z-index: 1019; background: #fff; border-bottom: 1px solid #e0e0e0; display: flex; }
        .tab-strip .nav-link { flex: 1; text-align: center; color: #888; font-weight: 600; border: none; border-radius: 0; padding: 10px 4px; font-size: .9rem; border-bottom: 3px solid transparent; }
        .tab-strip .nav-link.active { color: var(--ap); border-bottom-color: var(--ap); }
        #pedidoTabsContent { padding-top: 14px; }
        #tab-productos-btn, #tab-carrito-btn { display: inline-flex; align-items: center; justify-content: center; gap: 14px; font-size: 1.1rem; font-weight: 700; }
        #tab-productos-btn .uil-shopping-basket, #tab-carrito-btn .uil-shopping-cart-alt { font-size: 1.45rem; }
        #cartTotalTab { font-size: 1.1rem; font-weight: 700; }
        .cart-ico-wrap { position: relative; display: inline-flex; align-items: center; }
        .cart-count-badge { position: absolute; top: -10px; right: -12px; min-width: 22px; height: 22px; padding: 0 5px; background: #dc3545; color: #fff; border: 2px solid #fff; border-radius: 11px; font-size: .78rem; font-weight: 800; line-height: 18px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
        .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1019; background: #fff; border-top: 1px solid #e0e0e0; padding: 8px 12px; }
        #tbProductos { display: block; width: 100%; max-width: 640px; margin: 0 auto; }
        #tbProductos tbody { display: block; }
        #tbProductos tr { display: flex; background: #fff; border-radius: 8px; margin: 4px 10px; border: 1px solid #e2e0f0; overflow: hidden; transition: background .15s, border-color .15s; }
        #tbProductos tr.item-activo { background: rgba(108,99,255,.06); border-color: #a89cf7; }
        #tbProductos td { display: block; width: 100%; padding: 0; }
        .prod-card { display: flex; align-items: center; padding: 8px 10px; gap: 10px; width: 100%; }
        .prod-img-wrap { position: relative; flex-shrink: 0; cursor: pointer; }
        .prod-img-wrap img { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; display: block; max-width: 56px; max-height: 56px; padding: 0; }
        .prod-fav { position: absolute; top: 3px; left: 3px; cursor: pointer; line-height: 1; }
        .prod-fav img { width: 20px; height: 20px; padding: 0; max-width: 20px; max-height: 20px; }
        .oferta-badge { position: absolute; bottom: 3px; left: 3px; background: #ffc107; color: #000; font-size: .6rem; font-weight: 700; padding: 1px 5px; border-radius: 4px; }
        .prod-info { flex: 1; min-width: 0; }
        .prod-name { font-size: .85rem; font-weight: 600; color: #333; margin-bottom: 3px; }
        .prod-price { color: var(--ap); font-weight: 700; font-size: .88rem; margin-bottom: 0; }
        .prod-attrs { display: flex; flex-wrap: wrap; gap: 4px; margin: 2px 0 4px; }
        .prod-attr { display: inline-flex; align-items: center; gap: 3px; font-size: .68rem; font-weight: 600; color: #6c63ff; background: #ede9ff; border-radius: 10px; padding: 1px 8px; line-height: 1.6; }
        .prod-attr i { font-size: .8rem; }
        .prod-actions { display: flex; align-items: center; flex-shrink: 0; margin-left: auto; padding-left: 8px; }
        .qty-row { display: none; align-items: center; gap: 4px; flex-wrap: nowrap; }
        .item-activo .qty-row { display: flex; }
        .btn-agregar { font-size: .78rem; background: var(--ap); color: #fff; border: none; border-radius: 8px; padding: 5px 12px; cursor: pointer; font-weight: 600; white-space: nowrap; }
        .btn-agregar:hover { background: #5a52d5; }
        .item-activo .btn-agregar { display: none; }
        .btn-qty { width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; font-weight: 700; line-height: 1; }
        .btn-qty i { display: block; line-height: 1; }
        .btn-qty-minus { background: #fff; color: var(--ap); border: 1.5px solid var(--ap); }
        .btn-qty-minus:hover { background: var(--ap); color: #fff; }
        .btn-qty-plus  { background: var(--am); color: #fff; border: 1.5px solid var(--am); }
        .btn-qty-plus:hover { background: #2ebf9a; border-color: #2ebf9a; }
        .qty-input { width: 46px; text-align: center; border: 1px solid #ddd; border-radius: 6px; padding: 3px 2px; font-size: .9rem; }
        .btn-comment { width: 32px; height: 32px; border-radius: 50%; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #6c757d; border: 1.5px solid #6c757d; background: #fff; cursor: pointer; }
        .btn-comment i { display: block; line-height: 1; }
        .btn-comment:hover { background: #6c757d; color: #fff; }
        .btn-comment.has-comment { color: #fff; background: var(--ap); border-color: var(--ap); }
        .cart-nota-row { display: flex; align-items: center; gap: 6px; font-size: .82rem; color: #666; background: #f8f7ff; border-radius: 6px; padding: 6px 8px; border-left: 3px solid var(--ap); }
        .cart-nota-row span { flex: 1; font-style: italic; }
        .cart-empty { text-align: center; padding: 60px 20px; color: #bbb; }
        .cart-empty .cart-icon { font-size: 3.5rem; display: block; margin-bottom: 12px; color: #ccc; }
        #tablapedido { font-size: .9rem; }
        #tablapedido thead th { color: #999; font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #e8e8e8; padding-bottom: 6px; }
        #tablapedido tbody td { vertical-align: middle; border-bottom: 1px solid #f1f1f1; padding: 8px 4px; }
        .cart-linenum { display: inline-block; min-width: 18px; text-align: center; font-size: .72rem; font-weight: 700; color: #aaa; }
        .cart-prod-name { color: #444 !important; text-decoration: none; text-transform: capitalize; font-weight: 500; }
        .cart-prod-name:hover { color: var(--ap) !important; }
        .cart-comment-ico { color: var(--ap); margin-right: 3px; }
        .cart-qty { display: inline-block; min-width: 28px; text-align: center; background: #ede9ff; color: var(--ap); border-radius: 10px; padding: 1px 8px; font-weight: 600; font-size: .8rem; }
        .cart-subtotal { font-weight: 700; color: #333; white-space: nowrap; }
        .cart-del { border: none; background: transparent; color: #dc3545; padding: 4px 6px; line-height: 1; cursor: pointer; border-radius: 8px; }
        .cart-del:hover { background: #fde8ea; }
        .cart-del i { font-size: 1.05rem; vertical-align: middle; }
        #tablapedido tfoot td { border-top: 2px solid #eee; padding-top: 12px; padding-bottom: 4px; }
        .cart-total-label { font-weight: 600; color: #666; text-transform: uppercase; font-size: .78rem; letter-spacing: .4px; }
        .cart-total-value { font-weight: 800; color: var(--ap); font-size: 1.1rem; white-space: nowrap; }
        .btn-send i, .btn-add-products i, .nav-link i { vertical-align: middle; }
        .bar-total { display: flex; flex-direction: column; line-height: 1.15; }
        .bar-total-top { display: flex; align-items: center; gap: 7px; margin-bottom: 1px; }
        .bar-total-label { font-size: .66rem; text-transform: uppercase; letter-spacing: .6px; color: #9a9a9a; font-weight: 700; }
        .bar-prod-count { font-size: .68rem; color: #fff; background: var(--ap); border-radius: 10px; padding: 1px 8px; font-weight: 700; line-height: 1.4; }
        .bar-prod-count:empty { display: none; }
        .bar-total-value { font-size: 1.45rem; font-weight: 800; color: var(--ap); letter-spacing: -.5px; }
        .btn-send { background: var(--am); border-color: var(--am); color: #fff; border-radius: 24px; padding: 7px 20px; font-weight: 600; }
        .btn-send:hover { background: #2ebf9a; border-color: #2ebf9a; color: #fff; }
        .btn-add-products { background: var(--ap); border-color: var(--ap); color: #fff; border-radius: 24px; font-weight: 600; }
        .btn-add-products:hover { background: #5a52d5; color: #fff; }
        .btn-observacion { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: var(--ap); border: 1.5px solid var(--ap); border-radius: 8px; padding: 6px 14px; font-size: .85rem; font-weight: 600; cursor: pointer; }
        .btn-observacion i { font-size: 1.05rem; line-height: 1; }
        .btn-observacion:hover { background: var(--ap); color: #fff; }
        .cargando { width:100%; height:100%; position:fixed; top:0; left:0; z-index:10000; display:flex; align-items:center; justify-content:center; background:rgba(240,242,245,.88); backdrop-filter:blur(3px); }
        .cargando-card { background:#fff; border-radius:16px; padding:32px 40px; box-shadow:0 8px 32px rgba(0,0,0,.12); display:flex; flex-direction:column; align-items:center; gap:14px; }
        .cargando-card .spinner-border { width:3rem; height:3rem; border-width:.3rem; color:var(--ap); }
        .cargando-card p { margin:0; font-weight:600; color:#555; font-size:.95rem; }
        #rubro { border-radius: 8px; border: 1px solid #ddd; }
        img { max-width: none !important; max-height: none !important; padding: 0 !important; }
    </style>
    <script>
        $(window).on('load', function(){ $(".loader").fadeOut("slow"); });
        var intervalo;
        runCargar();
        function runCargar() { intervalo = setInterval(terminaCarga, 200); }
        function terminaCarga() {
            if (document.getElementById("tablapedido")) {
                clearInterval(intervalo);
                document.getElementById('loader').style.display = 'none';
                initFavoritos();
                var idPedido = localStorage.getItem("idPedido");
                if (idPedido == null) {
                    localStorage.setItem("idPedido", <?php echo intval($_GET["ped"] ?? 0); ?>);
                } else {
                    if (<?php echo intval($_GET["ped"] ?? 0); ?> == localStorage.getItem("idPedido")) {
                        if (localStorage.getItem("pedido") != null) {
                            pedido = stringToArray(localStorage.getItem("pedido"));
                            for (var x = 0; x < pedido.length - 1; x++) {
                                if (pedido[x][0] != ".001") {
                                    document.getElementById('' + pedido[x][0]).value = pedido[x][2];
                                    if (parseInt(pedido[x][2]) > 0) { var _el = document.getElementById(pedido[x][0]); if (_el) { var _tr2 = _el.closest('tr'); if (_tr2) _tr2.classList.add('item-activo'); } }
                                }
                            }
                            listarPedidos();
                        }
                    } else {
                        localStorage.setItem("idPedido", <?php echo intval($_GET["ped"] ?? 0); ?>);
                    }
                }
                console.log("Listo");
            }
        }
        function arrayToString(a) { var r = ""; for (var i = 0; i < a.length; i++) { r += a[i].join() + ";"; } return r; }
        function stringToArray(s) { var array = []; var a = s.split(";"); for (var i = 0; i < a.length; i++) { array.push(a[i].split(",")); } return array; }
        var busquedaFlag = false;
        function NoBack() { history.go(1); }
        function busqueda() {
            document.getElementById('txBuscar').value = "";
            if (busquedaFlag) {
                document.getElementById('rubro').style.display = 'block';
                document.getElementById('txBuscar').style.display = 'none';
                document.getElementById("icoFiltro").className = 'uil uil-list-ul';
                listarRubro();
                busquedaFlag = false;
            } else {
                document.getElementById('rubro').style.display = 'none';
                document.getElementById('txBuscar').style.display = 'block';
                document.getElementById("icoFiltro").className = 'uil uil-search';
                busquedaFlag = true;
            }
        }
        var codigo = null, descripcion = null, precio = null, rubro = null, promo = null;
        var totales = null;
        var pedido = [];
        var anclaje = "", telefono = "", token = "", nombre = "", ped = "", clienteId = "";
        function MaysPrimera(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
        function verImagen(valor, desc, oferta) {
            document.getElementById("_producto").innerHTML = "(" + valor + ") " + desc;
            document.getElementById("imgAmplia").innerHTML = "<img src='../files/articulos/" + valor + ".jpg' class='img-fluid' style='max-width:100%;max-height:300px;' onerror=\"this.src='../files/articulos/camara.jpg';\">";
            var arr = getFavs();
            var _fav = "<a onclick='favorito(\"" + valor + "\")'><img src='img/estrella.png' id='img" + valor + "' style='max-width:24px;max-height:24px;padding:0;'> Favorito</a>";
            if (arr.indexOf(valor) != -1) _fav = "<a onclick='favorito(\"" + valor + "\")'><img src='img/estrella_ok.png' id='img" + valor + "' style='max-width:24px;max-height:24px;padding:0;'> Favorito</a>";
            document.getElementById("_favoritos").innerHTML = _fav;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('myModal')).show();
        }
        function filtroFavorito() {
            document.getElementById("rubro").selectedIndex = -1;
            var t = document.getElementById("tbProductos"); var n = t.rows.length;
            for (var j = 0; j < n; j++) { t.rows[j].style.display = (t.rows[j].cells[0].id).includes("f") ? "" : "none"; }
            document.documentElement.scrollTop = -180;
        }
        function irProductos() { bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-productos-btn')).show(); }
        function getFavs() {
            if (!telefono) return [];
            var v = localStorage.getItem("favorito_" + telefono);
            return v ? v.split(",").filter(Boolean) : [];
        }
        function setFavs(arr) {
            localStorage.setItem("favorito_" + telefono, arr.filter(Boolean).join(","));
        }
        function initFavoritos() {
            var arr = getFavs();
            if (arr.length === 0) return;
            arr.forEach(function(cod) {
                var img = document.getElementById("es" + cod);
                if (img) img.src = "img/_estrella_ok.png";
                var row = document.getElementById("c" + cod);
                if (row) row.id = "f" + cod;
            });
            var op = document.getElementById("rubro");
            if (op && (!op.options[0] || op.options[0].value !== "FAVORITOS")) {
                var o = document.createElement("option"); o.text = "★ Favoritos"; o.value = "FAVORITOS"; o.style.color = "#6c63ff"; o.style.fontWeight = "700"; op.add(o, op[0]);
            }
        }
        function favorito(cod) {
            var img1 = document.getElementById('img' + cod); // solo existe si el modal está abierto
            var img2 = document.getElementById('es' + cod);
            var arr = getFavs(); var idx = arr.indexOf(cod);
            if (idx !== -1) {
                if (img1) img1.src = "img/estrella.png";
                if (img2) img2.src = "img/_estrella.png";
                arr.splice(idx, 1); setFavs(arr);
                var favEl = document.getElementById("f" + cod); if (favEl) favEl.id = "c" + cod;
            } else {
                if (img1) img1.src = "img/estrella_ok.png";
                if (img2) img2.src = "img/_estrella_ok.png";
                arr.push(cod); setFavs(arr);
                var curEl = document.getElementById("c" + cod); if (curEl) curEl.id = "f" + cod;
                var op = document.getElementById("rubro");
                if (op && op.options[0] && op.options[0].value !== "FAVORITOS") { var o = document.createElement("option"); o.text = "★ Favoritos"; o.value = "FAVORITOS"; o.style.color = "#6c63ff"; o.style.fontWeight = "700"; op.add(o, op[0]); }
            }
        }
        function getCookie(k) {
            if (!k) return null;
            return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(k).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
        }
        function cerrar() { var w = window.open("about:blank", "_self"); setTimeout(function(){ w.close(); }, 1000); }
        function verHistorico() {}
        function listarRubro() {
            var str = document.getElementById("rubro").value;
            var t = document.getElementById("tbProductos"); var n = t.rows.length;
            if (str != 'FAVORITOS') {
                for (var j = 0; j < n; j++) { t.rows[j].style.display = ((rubro[j] || '').replace(/ /g, '_') != str) ? "none" : ""; }
            } else {
                for (var j = 0; j < n; j++) { t.rows[j].style.display = (t.rows[j].cells[0].id).match("f") ? "" : "none"; }
            }
            document.documentElement.scrollTop = -180;
        }
        function listarProductos() {
            var str = document.getElementById("txBuscar").value;
            var t = document.getElementById("tbProductos"); var n = t.rows.length;
            for (var j = 0; j < n; j++) { t.rows[j].style.display = (t.rows[j].className.toUpperCase().indexOf(str.toUpperCase()) == -1) ? "none" : ""; }
            document.documentElement.scrollTop = -180;
        }
        function irComentario(elmnt, valor, desc) {
            if (parseInt(document.getElementById(elmnt).value) > 0) {
                var index = buscarCodigo(pedido, codigo[parseInt(valor)]);
                document.getElementById("comment").value = (index >= 0) ? pedido[index][6] : '';
                document.getElementById("_desc").innerHTML = desc;
                document.getElementById("_codigo").value = elmnt;
                document.getElementById("_fila").value = valor;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('Mcomentario')).show();
            }
        }
        function irComentario2() { bootstrap.Modal.getOrCreateInstance(document.getElementById('Mcomentario2')).show(); }
        function agregarComentario2() {
            var obs = document.getElementById("_obs").value;
            if (obs.length > 0) {
                var index = buscarCodigo(pedido, '.001');
                if (index >= 0) { pedido[index][1] = obs; } else {
                    var a = new Array(7); a[0]=".001"; a[1]=decodeURI(obs); a[2]="1"; a[3]="0"; a[4]="0"; a[5]=clienteId; a[6]=""; pedido.push(a);
                }
            }
            var table = document.getElementById("tablapedido");
            for (var x = table.rows.length - 1; x > 0; x--) { table.deleteRow(x); }
            localStorage.setItem("pedido", arrayToString(pedido)); listarPedidos();
        }
        function agregarComentario() {
            var index = buscarCodigo(pedido, codigo[parseInt(document.getElementById("_fila").value)]);
            if (index >= 0) pedido[index][6] = document.getElementById("comment").value;
            var table = document.getElementById("tablapedido");
            for (var x = table.rows.length - 1; x > 0; x--) { table.deleteRow(x); }
            localStorage.setItem("pedido", arrayToString(pedido)); listarPedidos();
        }
        function setMult(elmnt) {
            var m = document.getElementById("mult" + elmnt), inp = document.getElementById(elmnt);
            if (m && inp) m.innerText = parseInt(inp.value) || 0;
        }
        function sumarCantidad(elmnt, valor) {
            document.getElementById(elmnt).value = parseInt(document.getElementById(elmnt).value) + 1;
            var fila = parseInt(valor), tabla = document.getElementById("tablapedido");
            var cantidad = parseInt(document.getElementById(elmnt).value);
            var index = buscarCodigo(pedido, codigo[fila]);
            if (index >= 0) {
                var s = pedido[index][6]; pedido[index] = new Array(7);
                pedido[index][0]=codigo[fila]; pedido[index][1]=descripcion[fila].toLowerCase();
                pedido[index][2]=cantidad+""; pedido[index][3]=parseFloat(cantidad*parseFloat(precio[fila])).toFixed(2);
                pedido[index][4]=precio[fila]; pedido[index][5]=clienteId; pedido[index][6]=s;
            } else {
                var a = new Array(7); a[0]=codigo[fila]; a[1]=descripcion[fila].toLowerCase(); a[2]=cantidad+"";
                a[3]=parseFloat(cantidad*parseFloat(precio[fila])).toFixed(2); a[4]=precio[fila]; a[5]=clienteId; a[6]=""; pedido.push(a);
            }
            for (var x = tabla.rows.length - 1; x > 0; x--) { tabla.deleteRow(x); }
            localStorage.setItem("pedido", arrayToString(pedido)); listarPedidos();
            setMult(elmnt);
            var _tr = document.getElementById(elmnt).closest('tr'); if (_tr) _tr.classList.toggle('item-activo', parseInt(document.getElementById(elmnt).value) > 0);
        }
        function agregarItem(elmnt, valor) { sumarCantidad(elmnt, valor); }
        function irProducto(valor) { anclaje = valor; bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-productos-btn')).show(); }
        function eliminarPedido(valor, valor1) {
            if (confirm("DESEA ELIMINAR: " + valor1 + " ?")) {
                var tabla = document.getElementById("tablapedido"), index = buscarCodigo(pedido, valor);
                if (index > 0) pedido.splice(index, index); else pedido = [];
                for (var x = tabla.rows.length - 1; x > 0; x--) { tabla.deleteRow(x); }
                localStorage.setItem("pedido", arrayToString(pedido)); listarPedidos();
            }
        }
        function buscarCodigo(matriz, valor) { for (var i = 0; i < matriz.length; i++) { if (matriz[i][0] == valor) return i; } return -1; }
        function getTotales(matriz) { var t = 0; for (var i = 0; i < matriz.length; i++) { t += parseFloat(matriz[i][3]); } return t.toFixed(2); }
        function getCantItems(matriz) { var c = 0; for (var i = 0; i < matriz.length; i++) { if (parseFloat(matriz[i][3]) > 0) c++; } return c; }
        function actualizarTabCarrito(tot) {
            var tt = document.getElementById("cartTotalTab"); if (tt) tt.innerHTML = "$" + tot;
            var cc = document.getElementById("cartCount");
            if (cc) { var n = getCantItems(pedido); cc.innerText = n; cc.style.display = n > 0 ? "" : "none"; }
        }
        function setCantidad(elmnt, valor, nueva) {
            nueva = Math.max(0, parseInt(nueva) || 0);
            document.getElementById(elmnt).value = nueva;
            var fila = parseInt(valor), tabla = document.getElementById("tablapedido");
            var index = buscarCodigo(pedido, codigo[fila]);
            if (nueva === 0) {
                if (index >= 0) pedido.splice(index, 1);
            } else if (index >= 0) {
                var s = pedido[index][6];
                pedido[index] = new Array(7);
                pedido[index][0]=codigo[fila]; pedido[index][1]=descripcion[fila].toLowerCase();
                pedido[index][2]=nueva+""; pedido[index][3]=parseFloat(nueva*parseFloat(precio[fila])).toFixed(2);
                pedido[index][4]=precio[fila]; pedido[index][5]=clienteId; pedido[index][6]=s;
            } else {
                var a = new Array(7); a[0]=codigo[fila]; a[1]=descripcion[fila].toLowerCase(); a[2]=nueva+"";
                a[3]=parseFloat(nueva*parseFloat(precio[fila])).toFixed(2); a[4]=precio[fila]; a[5]=clienteId; a[6]=""; pedido.push(a);
            }
            for (var x = tabla.rows.length - 1; x > 0; x--) { tabla.deleteRow(x); }
            localStorage.setItem("pedido", arrayToString(pedido)); listarPedidos();
            setMult(elmnt);
            var _tr = document.getElementById(elmnt).closest('tr'); if (_tr) _tr.classList.toggle('item-activo', nueva > 0);
        }
        function restarCantidad(elmnt, valor) {
            if (parseInt(document.getElementById(elmnt).value) > 0) {
                document.getElementById(elmnt).value = parseInt(document.getElementById(elmnt).value) - 1;
                var fila = parseInt(valor), tabla = document.getElementById("tablapedido");
                var cantidad = parseInt(document.getElementById(elmnt).value);
                var index = buscarCodigo(pedido, codigo[fila]);
                if (index >= 0) {
                    var s = pedido[index][7]; if (s == null) s = ""; pedido[index] = new Array(7);
                    pedido[index][0]=codigo[fila]; pedido[index][1]=descripcion[fila].toLowerCase();
                    pedido[index][2]=cantidad+""; pedido[index][3]=parseFloat(cantidad*parseFloat(precio[fila])).toFixed(2);
                    pedido[index][4]=precio[fila]; pedido[index][5]=clienteId; pedido[index][6]=s;
                } else {
                    var a = new Array(7); a[0]=codigo[fila]; a[1]=descripcion[fila].toLowerCase(); a[2]=cantidad+"";
                    a[3]=parseFloat(cantidad*parseFloat(precio[fila])).toFixed(2); a[4]=precio[fila]; a[5]=clienteId; a[6]=""; pedido.push(a);
                }
                for (var x = tabla.rows.length - 1; x > 0; x--) { tabla.deleteRow(x); }
                localStorage.setItem("pedido", arrayToString(pedido)); listarPedidos();
                setMult(elmnt);
                var _tr = document.getElementById(elmnt).closest('tr'); if (_tr) _tr.classList.toggle('item-activo', parseInt(document.getElementById(elmnt).value) > 0);
            }
        }
        function listarPedidos() {
            var table = document.getElementById("tablapedido");
            var lineNum = 0;
            for (var x = 0; x < pedido.length; x++) {
                if (pedido[x][3] > 0 || pedido[x][0] === ".001") {
                    var row = table.insertRow(-1);
                    if (pedido[x][0] === ".001") {
                        var cn = row.insertCell(0);
                        cn.colSpan = 5;
                        cn.innerHTML = "<div class='cart-nota-row'><i class='uil uil-comment-alt-lines'></i> <span>" + pedido[x][1] + "</span><button type='button' class='cart-del ms-auto' title='Quitar' onclick='eliminarPedido(\".001\",\"" + pedido[x][1] + "\")'><i class='uil uil-trash-alt'></i></button></div>";
                    } else {
                        lineNum++;
                        var _inp = document.getElementById(pedido[x][0]);
                        if (_inp) { _inp.value = pedido[x][2]; setMult(pedido[x][0]); var _tr2 = _inp.closest('tr'); if (_tr2) _tr2.classList.add('item-activo'); }
                        var comentario = "";
                        if (pedido[x][6] && pedido[x][6].length > 0) {
                            comentario = "<i class='uil uil-comment-alt-lines cart-comment-ico'></i>";
                            var el = document.getElementById("bt" + pedido[x][0]);
                            if (el) { el.classList.remove("btn-secondary"); el.classList.add("has-comment"); }
                        }
                        var c1 = row.insertCell(0), c2 = row.insertCell(1), c3 = row.insertCell(2), c4 = row.insertCell(3), c5 = row.insertCell(4);
                        c3.className = 'text-center'; c4.className = 'text-end'; c5.className = 'text-end';
                        c1.innerHTML = "<span class='cart-linenum'>" + lineNum + "</span>";
                        c2.innerHTML = comentario + "<a class='cart-prod-name' href='#' onclick='irProducto(\"" + pedido[x][0] + "\")'>" + pedido[x][1] + "</a>";
                        c3.innerHTML = "<span class='cart-qty'>" + pedido[x][2] + "</span>";
                        c4.innerHTML = "<span class='cart-subtotal'>$" + pedido[x][3] + "</span>";
                        c5.innerHTML = "<button type='button' class='cart-del' title='Quitar' onclick='eliminarPedido(\"" + pedido[x][0] + "\",\"" + pedido[x][1] + "\")'><i class='uil uil-trash-alt'></i></button>";
                    }
                } else { pedido.splice(x, 1); x--; }
            }
            var _tot = getTotales(pedido);
            actualizarTabCarrito(_tot);
            var btAgregar = document.getElementById("btAgegra");
            var _div1 = document.getElementById("_div1");
            var tbTitulo = document.getElementById("tbTitulo");
            var barTotalValue = document.getElementById("barTotalValue");
            if (barTotalValue) barTotalValue.innerHTML = "$" + _tot;
            var barProdCount = document.getElementById("barProdCount");
            if (barProdCount) barProdCount.innerHTML = lineNum > 0 ? lineNum + (lineNum === 1 ? " producto" : " productos") : "";
            if (pedido.length > 0) { btAgregar.style.display = "none"; _div1.style.display = ''; tbTitulo.style.display = ''; }
            else { btAgregar.style.display = ""; tbTitulo.style.display = 'none'; _div1.style.display = 'none'; }
        }
        function copia_portapapeles() {
            var textarea = document.getElementById("textarea"), miPedido = "";
            for (var i = 0; i < pedido.length; i++) { miPedido += pedido[i][0] + "-" + pedido[i][1] + ".." + pedido[i][2] + "\n"; }
            textarea.innerHTML = miPedido; textarea.select();
            try { var ok = document.execCommand('copy'); document.getElementById("copiar").innerHTML = ok ? 'Pedido Copiado!' : 'Incapaz de copiar!'; }
            catch(e) { document.getElementById("copiar").innerHTML = 'Browser no soportado!'; }
        }
        document.addEventListener('DOMContentLoaded', function() {
            var tabProd = document.getElementById('tab-productos-btn');
            var tabCart = document.getElementById('tab-carrito-btn');
            if (!tabProd || !tabCart) return;
            tabCart.addEventListener('shown.bs.tab', function() {
                document.getElementById('idBuscar').style.display = 'none';
                document.getElementById('idHistocico').style.display = 'flex';
                document.documentElement.scrollTop = -180;
            });
            tabProd.addEventListener('shown.bs.tab', function() {
                document.getElementById('idBuscar').style.display = 'block';
                document.getElementById('idHistocico').style.display = 'none';
                document.location.href = '#' + anclaje;
                document.documentElement.scrollTop = $(window).scrollTop() - 180;
            });
        });
        function enviarPedido() {
            if (getTotales(pedido) > 1000) {
                Swal.fire({ title:'', text:'¿Desea enviar el pedido ahora?', icon:'question', showCancelButton:true,
                    confirmButtonColor:'#727cf5', cancelButtonColor:'#fa5c7c', cancelButtonText:'Cancelar', confirmButtonText:'Aceptar'
                }).then(function(result){ if (result.isConfirmed) { document.getElementById('bloquea').style.display='block'; $('#btnEnviarPedidos').prop('disabled',true); enviarPedidoSeleccionado(); } });
            } else { Swal.fire({ text: "El importe minimo del pedido es de $1.000!" }); }
        }
        function enviarPedidoSeleccionado() {
            $('#btnEnviarPedidos').prop('disabled', true);
            var empresa = "<?php echo DB_NAME; ?>";
            var tenantSlug = "<?php echo $tenantSlug ?? ''; ?>";
            var url = "<?php echo tenantUrl($tenantSlug); ?>";
            var vedid = "<?php echo $_GET['ved'] ?? ''; ?>";
            var json = { type:"_cliente_msg", token:token, telefono:telefono, nombre:nombre, ped:ped, total:getTotales(pedido), mensaje:pedido, ved:vedid };
            $.ajax({ type:"POST", url:'S_Pedidos_bis.php', data:"json="+JSON.stringify(json)+"&t="+encodeURIComponent(tenantSlug),
                success: function(data) {
                    document.getElementById('bloquea').style.display = 'none';
                    if (parseInt(data) > 0) {
                        var mensaje = "*"+nombre+"* Su pedido a sido confirmado. \n";
                        mensaje += "*Pedido N°:* "+ped+"\n";
                        mensaje += "*Monto: $* "+getTotales(pedido)+"\n";
                        mensaje += "*Costo de envio:$* 0.00 \n";
                        mensaje += "*Ticket:* 👇\n\n";
                        mensaje += url+"/reportes/exTicket.php?id="+ped+"\n\n";
                        $.ajax({ type:'POST', url:'send_wa.php', data:{ to:telefono, text:mensaje, ped:ped, t:tenantSlug },
                            complete: function(){ pedido = []; location.href = 'finaliza.php?t=<?php echo $tenantSlug; ?>'; }
                        });
                    } else { alert("Ocurrio un error inesperado"); }
                }
            });
        }
        function openWSConnection(hostname, port, endpoint, mensaje) {
            try {
                var ws = new WebSocket(hostname + endpoint);
                ws.onopen = function() { ws.send(mensaje); pedido = []; ws.close(); location.href = "finaliza.php?t=<?php echo $tenantSlug; ?>"; };
                ws.onclose = function(e) { console.log("WS CLOSE", e); };
                ws.onerror = function(e) { console.log("WS ERROR", e); };
            } catch(e) { console.error(e); }
        }
    </script>
</head>
<body onload="NoBack();">
<div class="loader" id="loader"></div>

<?php
$lista = "lista1";
if (isset($_GET["ped"])) {
    $responseWebMaster = getWebMasterConfig();

    $favorito_array = []; // favoritos se manejan en localStorage por telefono
    $row = null;
    if ($responseWebMaster['data']['b2b']) {
        $row = mysqli_fetch_array(Connection::runQuery("SELECT link_pedidos.*,link_pedidos.telefono as cel, clientes.* FROM `link_pedidos` inner join clientes on link_pedidos.clienteId=clientes.codigo where link_pedidos.id='".$_GET["ped"]."' and link_pedidos.estado=0"));
    } else if ($responseWebMaster['data']['b2c']) {
        $row = mysqli_fetch_array(Connection::runQuery("SELECT link_pedidos.*,link_pedidos.telefono as cel, clientes.* FROM `link_pedidos` inner join clientes on link_pedidos.clienteId=clientes.id where link_pedidos.id='".$_GET["ped"]."' and link_pedidos.estado=0"));
    }

    if ($row != NULL) {
        $deposito = $row["deposito"];
        $lista = "lista".($row["lista"] ?: '1');
        $cRazon   = $row["razonSocial"];
        $cDir     = $row["direccion"];
        $cCel     = $row["cel"];
        $cToken   = $row["token"];
        $cId      = $row["clienteId"];
        echo "<script> telefono='".addslashes($cCel)."'; token='".addslashes($cToken)."'; nombre='".addslashes($cRazon)."'; ped='".$_GET["ped"]."'; clienteId='".addslashes($cId)."'; </script>";
?>

    <!-- Fixed top navbar -->
    <nav class="navbar fixed-top navbar-atiende px-3 py-2 d-flex align-items-center">
        <div>
            <div class="client-name"><?php echo htmlspecialchars($cRazon); ?></div>
            <div class="client-addr"><?php echo htmlspecialchars($cDir); ?></div>
        </div>
        <img src="../public/img/logo_lateral.png" alt="Atiende" height="34" class="ms-auto">
    </nav>

    <!-- Tab strip -->
    <div class="tab-strip">
        <ul class="nav w-100" id="pedidoTabs" role="tablist">
            <li class="nav-item flex-fill">
                <button class="nav-link w-100" id="tab-productos-btn" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">
                    <i class="uil uil-shopping-basket"></i> Productos
                </button>
            </li>
            <li class="nav-item flex-fill">
                <button class="nav-link active w-100" id="tab-carrito-btn" data-bs-toggle="tab" data-bs-target="#menu1" type="button" role="tab">
                    <span class="cart-ico-wrap"><i class="uil uil-shopping-cart-alt"></i><span class="cart-count-badge" id="cartCount" style="display:none;">0</span></span>
                    <span id="cartTotalTab">$0.00</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab content -->
    <div class="tab-content" id="pedidoTabsContent">

        <!-- PRODUCTOS -->
        <div class="tab-pane fade" id="home" role="tabpanel">
            <table id="tbProductos" width="auto">
                <tbody>
                <?php
                $result = Connection::runQuery("SELECT * FROM `articulos` where 1 order by linea,rubro");
                $codigo_js = ""; $descripcion_js = ""; $precio_js = ""; $rubro_js = "";
                $fila = 0;
                while ($row = mysqli_fetch_array($result)) {
                    $d = explode("|", $row["deposito"] ?? $deposito);
                    if (is_null($row["deposito"]) || strlen(array_search($deposito, $d)) > 0) {
                        $img_favorito = "img/_estrella.png"; $fav = "c";
                        if ($row["oferta"] == "1") { $fav = "o"; }
                        $imagen = file_exists("../files/articulos/".$row["codigo"].".jpg")
                            ? "../files/articulos/".$row["codigo"].".jpg?".date("YmdHis")
                            : "../files/articulos/camara.jpg";
                        $row[$lista] += ($row[$lista] * $row["iva"] / 100) + $row["impInt"];
                        $codigo_js      .= "'".$row["codigo"]."',";
                        $descripcion_js .= "'".$row["descripcion"]."',";
                        $precio_js      .= "'".$row[$lista]."',";
                        $rubro_js       .= "'".$row["rubro"]."',";
                        $descClean  = ucwords(strtolower($row["descripcion"]));
                        $priceLabel = number_format($row[$lista], 2, '.', '')." x ".$row["pack"];
                        echo "<tr id='".$fila.str_replace(" ","_",$row["rubro"])."' class='".$row["descripcion"]."'>";
                        echo "<td id='".$fav.$row["codigo"]."'>";
                        ?>
                        <div class="prod-card">
                            <div class="prod-img-wrap" onclick="verImagen('<?php echo $row["codigo"]; ?>','<?php echo $row["descripcion"]; ?>','<?php echo $row["detalle_oferta"]; ?>')">
                                <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($row["descripcion"]); ?>" onerror="this.src='../files/articulos/camara.jpg'">
                                <span class="prod-fav" onclick="event.stopPropagation(); favorito('<?php echo $row["codigo"]; ?>')">
                                    <img id="es<?php echo $row["codigo"]; ?>" src="<?php echo $img_favorito; ?>" alt="">
                                </span>
                                <?php if ($row["oferta"] == "1") echo "<span class='oferta-badge'>".htmlspecialchars($row["detalle_oferta"])." OFF</span>"; ?>
                            </div>
                            <div class="prod-info">
                                <div class="prod-name"><?php echo $descClean; ?></div>
                                <?php
                                $attrs = [];
                                if (trim((string)($row["color"]  ?? '')) !== '') $attrs[] = '<span class="prod-attr"><i class="uil uil-palette"></i> '.htmlspecialchars($row["color"]).'</span>';
                                if (trim((string)($row["tamano"] ?? '')) !== '') $attrs[] = '<span class="prod-attr"><i class="uil uil-tshirt"></i> '.htmlspecialchars($row["tamano"]).'</span>';
                                if ($attrs) echo '<div class="prod-attrs">'.implode('', $attrs).'</div>';
                                ?>
                                <div class="prod-price">$<?php echo number_format($row[$lista], 2, '.', ''); ?> x <span class="prod-mult" id="mult<?php echo $row["codigo"]; ?>">0</span></div>
                            </div>
                            <div class="prod-actions">
                                <button type="button" class="btn-agregar" onclick="agregarItem('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?>)">+ Agregar</button>
                                <div class="qty-row">
                                    <button type="button" class="btn-qty btn-qty-minus" onclick="restarCantidad('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?>)"><i class="uil uil-minus"></i></button>
                                    <input class="qty-input" type="number" min="0" id="<?php echo $row["codigo"]; ?>" value="0" onchange="setCantidad('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?>,this.value)" inputmode="numeric">
                                    <button type="button" class="btn-qty btn-qty-plus" onclick="sumarCantidad('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?>)"><i class="uil uil-plus"></i></button>
                                    <button type="button" class="btn-comment" id="bt<?php echo $row["codigo"]; ?>" onclick="irComentario('<?php echo $row["codigo"]; ?>',<?php echo $fila; ?>,' <?php echo $descClean; ?>')"><i class="uil uil-comment-alt-lines"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php
                        echo "</td></tr>";
                        $fila++;
                    }
                }
                echo "<script>codigo=[".$codigo_js."]; descripcion=[".$descripcion_js."]; precio=[".$precio_js."]; rubro=[".$rubro_js."]; listarRubro();</script>";
                if (isset($_GET["promo"])) echo "<script>promo=".intval($_GET["promo"])."</script>";
                ?>
                </tbody>
            </table>
        </div>

        <!-- CARRITO -->
        <div class="tab-pane fade show active" id="menu1" role="tabpanel">
            <div style="padding: 16px;">
                <div id="btAgegra">
                    <div class="cart-empty">
                        <span class="cart-icon uil uil-shopping-cart-alt"></span>
                        <p class="mb-3 text-muted">Tu carrito está vacío</p>
                        <button type="button" class="btn btn-add-products" onclick="irProductos()"><i class="uil uil-plus"></i> Agregar productos</button>
                    </div>
                </div>
                <table class="table table-sm" id="tablapedido">
                    <thead id="tbTitulo" style="display:none">
                        <tr><th style="width:24px;color:#bbb">#</th><th>Producto</th><th class="text-center">Cant</th><th class="text-end">Subtotal</th><th></th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div id="_div1" style="display:none; margin-top:8px;">
                    <button class="btn-observacion" onclick="irComentario2()"><i class="uil uil-notes"></i> Nota del Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="bottom-bar">
        <div id="idBuscar" style="display:none;">
            <div class="input-group">
                <button class="btn btn-outline-secondary" type="button" onclick="busqueda()"><i id="icoFiltro" class="uil uil-list-ul"></i></button>
                <input type="text" class="form-control" id="txBuscar" style="display:none;" onkeyup="listarProductos()" placeholder="Buscar producto...">
                <?php
                $result = Connection::runQuery("SELECT DISTINCT `rubro` FROM `articulos` ORDER BY rubro ASC");
                echo "<select class='form-select' id='rubro' onchange='listarRubro()' style='display:block;'>";
                // opcion FAVORITOS se agrega via JS segun localStorage del telefono
                while ($r = mysqli_fetch_array($result)) {
                    echo "<option value='".str_replace(" ","_",$r["rubro"])."'>".$r["rubro"]."</option>";
                }
                echo "</select>";
                ?>
            </div>
        </div>
        <div id="idHistocico" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div class="bar-total">
                <span class="bar-total-top">
                    <span class="bar-total-label">Total</span>
                    <span class="bar-prod-count" id="barProdCount"></span>
                </span>
                <span class="bar-total-value" id="barTotalValue">$0.00</span>
            </div>
            <button type="button" class="btn btn-send" id="btnEnviarPedidos" onclick="enviarPedido();">
                <i class="uil uil-message"></i> Enviar pedido
            </button>
        </div>
    </div>

    <!-- Blocking overlay -->
    <div id="bloquea" class="cargando" style="display:none;">
        <div class="cargando-card">
            <div class="spinner-border" role="status"><span class="visually-hidden">Enviando...</span></div>
            <p>Enviando pedido...</p>
        </div>
    </div>

    <!-- Modal: imagen producto -->
    <div class="modal fade" id="myModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="_producto"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="imgAmplia"></div>
                    <div class="mt-2"><span class="badge bg-warning text-dark" id="_promo"></span></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <span id="_favoritos"></span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: comentario producto -->
    <div class="modal fade" id="Mcomentario" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="uil uil-comment-alt-lines me-1"></i> Observación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="_codigo">
                    <input type="hidden" id="_fila">
                    <div class="fw-bold mb-1" id="_desc"></div>
                    <p class="text-muted small mb-2">Agregá una indicación para este producto (ej: marca, presentación, etc.)</p>
                    <textarea class="form-control" rows="4" id="comment" name="comment" placeholder="Escribí tu observación..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="agregarComentario()"><i class="uil uil-check"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: comentario general -->
    <div class="modal fade" id="Mcomentario2" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="uil uil-notes me-1"></i> Nota del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Indicaciones generales para todo el pedido (ej: horario de entrega, referencias, etc.)</p>
                    <textarea class="form-control" rows="4" id="_obs" name="_obs" placeholder="Escribí una nota para el pedido..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="agregarComentario2()"><i class="uil uil-check"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    if (promo != null) {
        bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-productos-btn')).show();
        document.getElementById("rubro").selectedIndex = document.getElementById("rubro").length - 1;
        document.getElementById('idBuscar').style.display = 'block';
        document.getElementById('idHistocico').style.display = 'none';
        listarRubro();
        document.documentElement.scrollTop = $(window).scrollTop() - 180;
    }
    </script>

<?php
    } else { ?>
        <div class="container mt-5">
            <div class="alert alert-danger"><strong>Error:</strong> La credencial no existe o está vencida. Solicite nuevamente el link para hacer un pedido.</div>
        </div>
<?php } } else { ?>
    <div class="container mt-5">
        <div class="alert alert-danger"><strong>Error:</strong> La aplicación no está disponible sin credencial.</div>
    </div>
<?php } ?>

</body>
</html>
