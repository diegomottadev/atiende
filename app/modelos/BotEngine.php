<?php
if (!defined('__ROOT__')) define('__ROOT__', dirname(__DIR__));
require_once __ROOT__ . '/config/Connection.php';
require_once __ROOT__ . '/config/Conexion.php';
require_once __ROOT__ . '/config/WhatsAppClient.php';

class BotEngine
{
    private $menuJson;
    private $tenantConfig;
    private $client;
    private $empresa;
    private $contactoMensaje;

    private $empresaNombre;

    public function __construct($menuJson, $tenantConfig, $client)
    {
        $this->menuJson        = $menuJson;
        $this->tenantConfig    = $tenantConfig;
        $this->client          = $client;
        $this->empresa         = $tenantConfig['data']['identificador'] ?? '';
        $this->empresaNombre   = $tenantConfig['data']['empresa_nombre'] ?? $this->empresa;
        $this->contactoMensaje = '';
    }

    public function handle($user, $pushname, $body, $type, $location = null)
    {
        // ---- Baja / opt-out (palabras estándar, coincidencia exacta del mensaje) + confirmación ----
        $bodyNorm     = strtolower(trim((string) $body));
        $palabrasBaja = ['baja', 'stop', 'cancelar', 'desuscribir', 'bajacp']; // 'bajacp' = legacy
        $esPedidoBaja = in_array($bodyNorm, $palabrasBaja, true);

        // ¿Hay una baja pendiente de confirmar? (estado guardado en contactos.anterior)
        $bajaPendiente = false;
        $reqEstado = Connection::runQuery("SELECT anterior FROM contactos WHERE id = '" . $user . "'");
        if ($reqEstado && mysqli_num_rows($reqEstado) > 0) {
            $antDec = json_decode(mysqli_fetch_assoc($reqEstado)['anterior'] ?? '', true);
            $bajaPendiente = is_array($antDec) && (($antDec['type'] ?? '') === 'baja_pendiente');
        }

        if ($bajaPendiente) {
            if (in_array($bodyNorm, ['si', 'sí', 's'], true)) {
                Connection::runQuery("DELETE FROM `telefonos` WHERE `telefono` like '" . $user . "'");
                Connection::runQuery("UPDATE `contactos` SET `anterior`='', menu='0', esperaRespuesta=0 WHERE id like '" . $user . "'");
                $this->client->sendText($user, 'Listo, te diste de baja. No recibirás más mensajes. Si querés volver, escribí *hola* cuando quieras. ¡Gracias!');
            } else {
                Connection::runQuery("UPDATE `contactos` SET `anterior`='' WHERE id like '" . $user . "'");
                $this->client->sendText($user, 'Cancelamos la baja, seguís suscripto. 🙂 Escribí *hola* para ver el menú.');
            }
            return;
        }

        if ($esPedidoBaja) {
            $antJson = addslashes(json_encode(['type' => 'baja_pendiente'], JSON_UNESCAPED_UNICODE));
            Connection::runQuery("UPDATE `contactos` SET `anterior`='" . $antJson . "', esperaRespuesta=0 WHERE id like '" . $user . "'");
            $this->client->sendText($user, '¿Confirmás darte de baja? No recibirás más mensajes. Respondé *SI* para confirmar.');
            return;
        }

        // Recuperar estado de sesión
        $menuID                 = '0';
        $esperaRespuesta        = '0';
        $ctrlLocationSendByChat = null;
        $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior FROM contactos where telefono LIKE '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row             = mysqli_fetch_assoc($request);
            // Un contacto sin menú (NULL/'') está en estado inicial: tratarlo como '0'
            $menuID          = ($row['menu'] === null || $row['menu'] === '') ? '0' : $row['menu'];
            $esperaRespuesta = $row['esperaRespuesta'];
            if ($menuID == 802) {
                $ctrlLocationSendByChat = $row['anterior'];
            }
        }

        // Código de cliente vinculado
        $codigoCliente = '';
        $request = Connection::runQuery("SELECT clienteId FROM `telefonos` WHERE `telefono` = '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row           = mysqli_fetch_assoc($request);
            $codigoCliente = $row['clienteId'];
        }

        // Palabras clave: SOLO al inicio (no esperando respuesta). Si el usuario está
        // escribiendo el detalle de un reclamo/consulta, su texto puede contener
        // palabras como "pedido" y NO debe secuestrar el flujo hacia otro menú.
        if ($esperaRespuesta == '0' && strlen($codigoCliente) > 0) {
            $menuclave = $this->buscarMenuClave($this->menuJson, $body);
            if (strlen($menuclave) > 0) {
                $menuID = $menuclave;
            }
        }

        // Inyectar motivos desde DB
        $rows    = [];
        $request = Connection::runQuery("SELECT `opcionId`, `opcion`, `menuId`, IF(guardar, 'true', 'false') guardar, `area` FROM `motivo_reclamos`");
        if ($request) {
            while ($row = mysqli_fetch_assoc($request)) {
                $rows[] = $row;
            }
            $rows[] = ['opcionId' => '0', 'opcion' => 'Salir', 'menuId' => '2.2', 'guardar' => false, 'area' => ''];
            foreach ($this->menuJson as $idx => $entry) {
                if (($entry['menuId'] ?? '') === '1') {
                    $this->menuJson[$idx]['menuItem'] = $rows;
                    break;
                }
            }
        }

        $rows    = [];
        $request = Connection::runQuery("SELECT `opcionId`, `opcion`, `menuId`, IF(guardar, 'true', 'false') guardar, `area` FROM `motivo_consultas`");
        if ($request) {
            while ($row = mysqli_fetch_assoc($request)) {
                $rows[] = $row;
            }
        }
        // Solo sobreescribir si la tabla tiene datos; si no, usar el menú definido en el JSON
        if (count($rows) > 0) {
            $rows[] = ['opcionId' => '0', 'opcion' => 'Salir', 'menuId' => '2.2', 'guardar' => false, 'area' => ''];
            foreach ($this->menuJson as $idx => $entry) {
                if (($entry['menuId'] ?? '') === '300') {
                    $this->menuJson[$idx]['menuItem'] = $rows;
                    break;
                }
            }
        }

        // Despachar
        if ($type === 'text') {
            $this->procesarAccion($menuID, $esperaRespuesta, $body, $pushname, $user, $codigoCliente);
        } elseif ($type === 'location' && $menuID == 802 && $ctrlLocationSendByChat !== null) {
            $locationBody = json_encode([$location['latitude'], $location['longitude']]);
            $this->procesarAccion($menuID, $esperaRespuesta, $locationBody, $pushname, $user, $codigoCliente);
        } else {
            $this->client->sendText($user, 'No está admitido mensaje de tipo ' . $type);
        }
    }

    // ¿El texto es un saludo / pedido de inicio? (hola, buenas, menú, etc.)
    private function esSaludo($texto)
    {
        $t = strtolower(trim((string) $texto));
        $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $t = trim(preg_replace('/[^a-z ]/', '', $t)); // solo letras y espacios (quita emojis/signos/números)
        if ($t === '') return false;
        $exactos = ['hola','holaa','holaaa','ola','buenas','buen dia','buenos dias','buenas tardes','buenas noches','hi','hello','hey','menu','inicio','empezar','comenzar','que tal','holis'];
        if (in_array($t, $exactos, true)) return true;
        foreach (['hola','buenas','buen dia','buenos dias','hello'] as $pref) {
            if (strpos($t, $pref) === 0) return true;
        }
        return false;
    }

    // Proyecta un array de menuItem aplicando visibilidad ("activo") y renumerando 1..N al vuelo.
    // Es el punto único de verdad: display y match consumen la MISMA proyección, así el número
    // que el usuario ve es exactamente el que matchea.
    private function proyectarMenu(array $menuItem): array
    {
        $visibles = []; $salir = null;
        foreach ($menuItem as $opt) {
            // Captura de texto libre: opcionId vacío (cadena ''), NO el '0' del botón Salir.
            // Se detecta por strlen (igual que el bucle de match) para que una captura cuyo
            // destino es '2.2' (detalle de consulta / consultar-reclamo) NO se confunda con Salir.
            if (strlen((string) ($opt['opcionId'] ?? '')) === 0) {
                $visibles[] = $opt; continue;            // se conserva tal cual, no se renumera, sea cual sea su menuId destino
            }
            if (($opt['menuId'] ?? '') === '2.2') { $salir = $opt; continue; } // Salir → al final
            $activo = array_key_exists('activo', $opt) ? $opt['activo'] : 'true'; // legacy = visible
            if ($activo === 'false') continue;            // oculta
            $visibles[] = $opt;
        }
        $n = 1;
        foreach ($visibles as &$o) { if (!empty($o['opcionId'])) { $o['opcionId'] = (string)$n; $n++; } }
        unset($o);
        if ($salir !== null) { $salir['opcionId'] = (string)$n; $salir['activo'] = 'true'; $visibles[] = $salir; }
        return $visibles;
    }

    private function procesarAccion($menu, $esperaRespuesta, $mensaje, $pushname, $user, $codigoCliente)
    {
        if ($menu === null || $menu === '') { $menu = '0'; } // estado inicial
        $mensajePorcesado = '';
        $espera_respuesta = '0';
        $numeroReclamo    = '';
        $numeroConsulta   = '';

        $codigoVendedor = '';
        $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior,vendedor_codigo FROM contactos where telefono LIKE '" . $user . "'");
        if (mysqli_num_rows($request) > 0) {
            $row            = mysqli_fetch_assoc($request);
            $anterior       = json_decode($row['anterior'], TRUE)['opcion'];
            $codigoVendedor = $row['vendedor_codigo'] ?? '';
        }

        if ($menu == '0') {
            // El menú de inicio solo se dispara ante un saludo (hola, buenas, etc.).
            // Evita que el bot reenvíe el menú ante cualquier mensaje suelto (p. ej. después de una baja).
            if (!$this->esSaludo($mensaje)) {
                error_log('[BotEngine] menu=0 sin saludo, no se responde. msg=' . substr($mensaje, 0, 30));
                return;
            }
            if (strlen($codigoVendedor) > 0) {
                $menu = '106'; // vendedor reconocido → pedir código de cliente
            } elseif (strlen($codigoCliente) == 0) {
                $menu = $this->menuJson[0]['menuIdB'];
            } else {
                $menu = '200'; // cliente identificado → ir directo al menú principal
            }
        }

        error_log('[BotEngine] procesarAccion menu=' . $menu . ' espera=' . $esperaRespuesta . ' cliente=' . $codigoCliente);

        for ($i = 0; $i < count($this->menuJson); $i++) {

            if ($this->menuJson[$i]['menuId'] == $menu) {

                error_log('[BotEngine] menu encontrado en i=' . $i . ' finaliza=' . json_encode($this->menuJson[$i]['finaliza']));

                if ($esperaRespuesta == '0') {

                    $mensajePorcesado = $this->menuJson[$i]['consigna'] . "\n";
                    error_log('[BotEngine] consigna len=' . strlen($mensajePorcesado));

                    $hayMenuItem = false;

                    $menuItem = $this->proyectarMenu($this->menuJson[$i]['menuItem'] ?? []);
                    error_log('[BotEngine] menuItem count=' . count($menuItem));

                    for ($j = 0; $j < count($menuItem); $j++) {
                        $opciones = $menuItem[$j]['opcion'];
                        if (strlen($opciones) > 0) {
                            $mensajePorcesado .= '*' . $menuItem[$j]['opcionId'] . '*. ' . $menuItem[$j]['opcion'] . "\n";
                        }
                        $hayMenuItem = true;
                    }

                    error_log('[BotEngine] hayMenuItem=' . ($hayMenuItem ? 'true' : 'false'));
                    if ($hayMenuItem) $espera_respuesta = '1';
                    if ($this->menuJson[$i]['finaliza'] == 'true') {
                        $menu = '0';
                    }

                    error_log('[BotEngine] antes registrarContacto pushname=' . $pushname);
                    try {
                        $this->registrarContacto($pushname, $user, $menu, $espera_respuesta);
                    } catch (Throwable $re) {
                        error_log('[BotEngine] registrarContacto FALLO: ' . $re->getMessage());
                    }
                    error_log('[BotEngine] despues registrarContacto');

                    $mensajePorcesado = str_replace('<saludo>',  $this->getSaludo(),     $mensajePorcesado);
                    $mensajePorcesado = str_replace('<nombre>',  $pushname,               $mensajePorcesado);
                    $mensajePorcesado = str_replace('<empresa>', $this->empresaNombre,     $mensajePorcesado);

                    $notiPedido   = '';
                    $notiEncuesta = '';
                    $esPromo      = false;
                    if (strpos($mensajePorcesado, '<linkPedidos>') !== false) {
                        // El placeholder se reemplaza más abajo por el link real (mismo mensaje)
                        $notiPedido = Connection::runQueryID("INSERT INTO `link_pedidos`(`clienteId`, `telefono`,token, `fecha`, `estado`) VALUES ('" . $codigoCliente . "','" . $user . "','" . $this->empresa . "',now(),0)");
                    }
                    if (strpos($mensajePorcesado, '<linkPromo>') !== false) {
                        $mensajePorcesado = str_replace('<linkPromo>', '', $mensajePorcesado);
                        $notiPedido = 'promo';
                        $esPromo    = true;
                    }
                    if (strpos($mensajePorcesado, '<linkEncuesta>') !== false) {
                        $mensajePorcesado = str_replace('<linkEncuesta>', '', $mensajePorcesado);
                        $notiEncuesta = $codigoCliente;
                    }

                    // Construir el link del pedido y dejarlo DENTRO del mismo mensaje (un solo bubble)
                    if (!$esPromo && strlen($notiPedido) > 0) {
                        $vendedorR   = '';
                        if (strlen($codigoVendedor) > 0) {
                            // Vendedor identificado por código (sesión en contactos.vendedor_codigo)
                            $reqAt = Connection::runQuery("SELECT atencion FROM vendedores WHERE codigo = '" . Connection::escape($codigoVendedor) . "'");
                            if ($reqAt && mysqli_num_rows($reqAt) > 0) {
                                $at = mysqli_fetch_assoc($reqAt)['atencion'];
                                if ($at !== null && $at !== '') {
                                    Connection::runQuery("UPDATE `link_pedidos` SET `clienteId`= '" . Connection::escape($at) . "' where id = '" . $notiPedido . "'");
                                    $vendedorR = $codigoVendedor;
                                    Connection::runQuery("UPDATE `vendedores` SET `atencion`= '' where codigo = '" . Connection::escape($codigoVendedor) . "'");
                                }
                            }
                        } else {
                            $requestVend = Connection::runQuery("SELECT atencion  FROM vendedores where telefono= '" . $user . "'");
                            if (mysqli_num_rows($requestVend) > 0) {
                                $rowVendedor = mysqli_fetch_assoc($requestVend);
                                if ($rowVendedor['atencion'] !== null) {
                                    Connection::runQuery("UPDATE `link_pedidos` SET `clienteId`= '" . Connection::escape($rowVendedor['atencion']) . "'  where id = '" . $notiPedido . "'");
                                    $requestVendedor = Connection::runQuery("SELECT codigo  FROM vendedores where atencion= '" . $rowVendedor['atencion'] . "'");
                                    if (mysqli_num_rows($requestVendedor) > 0) {
                                        $rowVendedor = mysqli_fetch_assoc($requestVendedor);
                                        $vendedorR   = $rowVendedor['codigo'];
                                        Connection::runQuery("UPDATE `vendedores` SET `atencion`= ''  where telefono= '" . $user . "'");
                                    }
                                }
                            }
                        }
                        $pedidoUrl = tenantUrl($this->empresa, '/pedidos/' . $notiPedido . ($vendedorR !== '' ? '/' . $vendedorR : ''));
                        if (strpos($mensajePorcesado, '<linkPedidos>') !== false) {
                            $mensajePorcesado = str_replace('<linkPedidos>', $pedidoUrl, $mensajePorcesado);
                        } else {
                            $mensajePorcesado = rtrim($mensajePorcesado) . "\n" . $pedidoUrl;
                        }
                    }

                    error_log('[BotEngine] enviando a ' . $user . ' msg=' . substr($mensajePorcesado, 0, 80));
                    $this->client->sendText($user, $mensajePorcesado);

                    // Promo: se envían como mensajes individuales (un producto por mensaje)
                    if ($esPromo) {
                        $request = Connection::runQuery("SELECT * FROM `promo` where estado =0");
                        if ($request) {
                            while ($row = mysqli_fetch_assoc($request)) {
                                $this->client->sendText($user, $row['precio'] . "\n" . tenantUrl($this->empresa, '/chatbot/promos/view.php?id=' . $row['id'] . '&cli=' . $codigoCliente));
                                usleep(1000);
                            }
                        }
                    }
                    if ($notiEncuesta != '') {
                        $this->client->sendLink($user, tenantUrl($this->empresa, '/chatbot/encuesta/index.php?encu=' . $notiEncuesta), 'Ax-Encuesta');
                    }
                    $esOpcionValida = true;

                } else {

                    $esOpcionValida = false;
                    $numeroReclamo  = '';
                    $numeroConsulta = '';
                    $menuItem       = $this->proyectarMenu($this->menuJson[$i]['menuItem'] ?? []);

                    for ($j = 0; $j < count($menuItem); $j++) {

                        if (strcasecmp($menuItem[$j]['opcionId'], $mensaje) == 0 || strlen($menuItem[$j]['opcionId']) == 0) {

                            if (strlen($menuItem[$j]['opcion']) > 0) {
                                $mensajePorcesado = 'Selecciono: *' . $menuItem[$j]['opcion'] . "*\n";
                            } else {
                                Connection::runQuery("UPDATE `contactos` SET `mensaje`= CONCAT(COALESCE(`mensaje`,''),'_','" . $mensaje . "')  where id = '" . $user . "'");
                            }

                            if ($menuItem[$j]['guardar'] == 'true') {
                                if (isset($menuItem[$j]['accion'])) {
                                    // accion is set — skip the generic reclamo save
                                } else {

                                    //* AQUI EMPIEZA REGISTRO DE RECLAMOS *//

                                    $motivo  = '';
                                    $detalle = '';
                                    if (strlen($menuItem[$j]['opcion']) > 0) {
                                        $motivo = $menuItem[$j]['opcion'];
                                    } else {
                                        if (isset($menuItem[$j]['motivo'])) {
                                            $motivo = $menuItem[$j]['motivo'];
                                        }
                                        $detalle = $mensaje;
                                    }

                                    //-----------VERIFICAR ANTERIOR---------------------------------/
                                    $_area   = $menuItem[$j]['area'];
                                    $_motivo = $motivo;
                                    $request = Connection::runQuery("SELECT anterior FROM contactos where telefono LIKE '" . $user . "'");
                                    if (mysqli_num_rows($request) > 0) {
                                        $row = mysqli_fetch_assoc($request);
                                        if (strlen($row['anterior']) > 0) {
                                            $_anterior = json_decode($row['anterior'], TRUE);
                                            $_area     = $_anterior['area'];
                                            $_motivo   = $_anterior['opcion'];
                                        }
                                    }

                                    $numeroReclamo = Connection::runQueryID("INSERT INTO `reclamos`(empresa,`fecha_ingreso`,`clienteId`, `telefono`,nick, `motivo`, `area`, `detalle`, resolucion) VALUES ('" . $this->empresa . "',now(),'" . $codigoCliente . "','" . $user . "','" . $pushname . "','" . Connection::escape($_motivo) . "','" . $_area . "','" . $detalle . "','')");
                                    Connection::runQuery("UPDATE `contactos` SET `anterior`= '' where id like '" . $user . "'");
                                    $request = Connection::runQuery("SELECT telefono,area FROM `areas` WHERE `id` = '" . $_area . "'");
                                    if (mysqli_num_rows($request) > 0) {
                                        $row            = mysqli_fetch_assoc($request);
                                        $telResponsable  = $row['telefono'];
                                        $areaResponsable = $row['area'];
                                        if (strlen($row['telefono']) > 0) {
                                            $request = null;
                                            if ($this->tenantConfig['data']['b2b']) {
                                                $request = Connection::runQuery("SELECT *  FROM clientes WHERE  `codigo` =  '" . $codigoCliente . "'");
                                            } else {
                                                $request = Connection::runQuery("SELECT *  FROM clientes WHERE  `id` = $codigoCliente");
                                            }

                                            if (mysqli_num_rows($request) > 0) {
                                                $row        = mysqli_fetch_assoc($request);
                                                $razonSocial = $row['razonSocial'];
                                                $vendedor    = $row['vendedor'];
                                                $direccion   = $row['direccion'];
                                            }

                                            $resultado  = '*‼️Este reclamo te ha sido informado porque estás asignado como supervisor del área ' . $areaResponsable . "*\n\n";
                                            $resultado .= 'Hay un nuevo reclamo de *' . $pushname . "*:\n" .
                                                '*Reclamo N°:* ' . $numeroReclamo . "\n" .
                                                '*Cliente:* ' . $razonSocial . "\n" .
                                                '*Dirección* ' . $direccion . "\n" .
                                                '*Vendedor:* ' . $vendedor . "\n" .
                                                '*Motivo:* ' . $_motivo . "\n" .
                                                '*Fecha:* ' . strftime('%Y-%m-%d %H:%M:%S', time()) . "\n" .
                                                '*Tel:* ' . substr($user, 3) . "\n" .
                                                '*Responder:* ' . tenantUrl($this->empresa, '/ws/m/movil.php?id=' . $numeroReclamo) . "\n";
                                            $this->client->sendText(trim($telResponsable), $resultado);
                                        }
                                    }

                                    //*  TERMINA REGISTRO DE RECLAMOS EN LA DB Y EL ENVIO DE POR WHATSAPP DEL RECLAMO GENERADO*//
                                }

                            } else {

                                if (isset($menuItem[$j]['accion'])) {

                                    if ($menuItem[$j]['accion'] === 'chequearVendedorCliente') {
                                        // Vendedor de sesión (guardado por registraVendedor en contactos.vendedor_codigo)
                                        $codVend = $codigoVendedor;

                                        // "Salir": cierra la sesión de vendedor.
                                        if (strcasecmp(trim($mensaje), 'salir') === 0) {
                                            Connection::runQuery("UPDATE `contactos` SET `vendedor_codigo`=NULL, `mensaje`='', `anterior`='', `esperaRespuesta`=0, `menu`='0' where id like '" . $user . "'");
                                            $this->client->sendText($user, 'Cerraste tu sesión de vendedor. ¡Hasta pronto!');
                                            return;
                                        }

                                        // Validar que el código de cliente exista y sea de este vendedor.
                                        $reqC = Connection::runQuery("SELECT razonSocial, codigo FROM clientes WHERE codigo = '" . Connection::escape($mensaje) . "' AND vendedor = '" . Connection::escape($codVend) . "'");
                                        if ($reqC && mysqli_num_rows($reqC) > 0) {
                                            $rowCliente = mysqli_fetch_assoc($reqC);
                                            Connection::runQuery("UPDATE `vendedores` SET `atencion`= '" . Connection::escape($rowCliente['codigo']) . "' where codigo like '" . Connection::escape($codVend) . "'");
                                            Connection::runQuery("UPDATE `contactos` SET `mensaje`='' where id like '" . $user . "'");
                                            $this->client->sendText($user, 'Cliente: ' . $rowCliente['razonSocial']);
                                        } else {
                                            // Reintento: NO se resetea el estado → el próximo mensaje es otro intento.
                                            $this->client->sendText($user, 'Codigo de cliente ingresado incorrecto intente nuevamente');
                                            return;
                                        }
                                    }

                                    if ($menuItem[$j]['accion'] === 'registraVendedor') {
                                        // Doble verificación: el código debe existir en ESTE tenant Y el número que
                                        // escribe ($user, ya normalizado a dígitos por el webhook) debe coincidir con
                                        // el vendedores.telefono registrado (formato WhatsApp 549…). Un número no
                                        // registrado no accede aunque conozca un código válido.
                                        $req = Connection::runQuery("SELECT codigo FROM vendedores WHERE codigo = '" . Connection::escape($mensaje) . "' AND telefono = '" . $user . "'");
                                        if ($req && mysqli_num_rows($req) > 0) {
                                            $rowV = mysqli_fetch_assoc($req);
                                            Connection::runQuery("UPDATE `contactos` SET `vendedor_codigo`= '" . Connection::escape($rowV['codigo']) . "', `mensaje`='' where id like '" . $user . "'");
                                        } else {
                                            // Mensaje genérico a propósito: no confirma si el código existe (evita filtrar
                                            // códigos válidos a un número no autorizado).
                                            $this->client->sendText($user, 'No pudimos identificarte como vendedor. Verificá tu código y escribí desde tu número registrado en el sistema.');
                                            Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0', `vendedor_codigo`=NULL where id like '" . $user . "'");
                                            return;
                                        }
                                    }

                                    if ($menuItem[$j]['accion'] == 'registraNumero') {
                                        $cli     = '';
                                        $request = null;

                                        if ($this->tenantConfig['data']['b2b']) {
                                            $request = Connection::runQuery("SELECT razonSocial,codigo  FROM clientes WHERE  `codigo` =  '" . $mensaje . "'");
                                        } else {
                                            $request = Connection::runQuery("SELECT razonSocial,id  FROM clientes WHERE  `id` =  '" . $mensaje . "'");
                                        }

                                        if (mysqli_num_rows($request) > 0) {
                                            $row            = mysqli_fetch_assoc($request);
                                            $cli            = $row['razonSocial'];
                                            $codigoCliente  = null;
                                            if ($this->tenantConfig['data']['b2b']) {
                                                $codigoCliente = $row['codigo'];
                                            } else {
                                                $codigoCliente = $row['id'];
                                            }
                                        }

                                        if (strlen($cli) > 0) {
                                            Connection::runQuery("REPLACE INTO `telefonos`( `clienteId`, `telefono`, `activo`)  VALUES ('" . $codigoCliente . "','" . $user . "',1)");
                                        } else {
                                            // Código inválido: cortar el flujo. Mandar el aviso, resetear el
                                            // contacto a estado inicial y NO seguir al menú (return temprano,
                                            // mismo patrón que chequearVendedorCliente/registraClientes).
                                            $this->client->sendText($user, 'El código de cliente no es válido. Volvé a intentarlo escribiendo *Hola* nuevamente.');
                                            Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '" . $user . "'");
                                            return;
                                        }
                                    }

                                    if ($menuItem[$j]['accion'] == 'consultarReclamo') {
                                        $re = $this->consultarReclamo(trim($mensaje), $user);
                                        if (strlen($re) > 0) {
                                            $this->client->sendText($user, str_replace('<saludo>', $this->getSaludo(), $re));
                                        } else {
                                            $this->client->sendText($user, 'El numero de reclamo no es valido.');
                                        }
                                    }

                                    if ($menuItem[$j]['accion'] == 'registraClientes') {
                                        if ($this->tenantConfig['data']['mix'] || $this->tenantConfig['data']['b2c']) {
                                            $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior,mensaje FROM contactos where telefono = '" . $user . "'");
                                            if (mysqli_num_rows($request) > 0) {
                                                $row                   = mysqli_fetch_assoc($request);
                                                $anterior              = json_decode($row['anterior'], TRUE)['opcion'];
                                                $this->contactoMensaje = $row['mensaje'];
                                            }
                                            $porciones    = explode('_', $this->contactoMensaje);
                                            $location     = substr($porciones[3] ?? '', 1, -1);
                                            $partLocation = explode(',', $location);
                                            if ($porciones[1] != null || $porciones[1] != '') {
                                                $codigoCliente = Connection::runQueryID("INSERT INTO `clientes`(`codigo`,`vendedor`, `razonSocial`, `direccion`, `ramo`, `zona`, `lista`,`latitud`,`longitud`,`deposito`,`telefono`) VALUES ('','1','" . Connection::escape($porciones[1]) . "','" . Connection::escape($porciones[2]) . "','RAMO','SIN_ZONA','1','" . Connection::escape($partLocation[0] ?? '') . "','" . Connection::escape($partLocation[1] ?? '') . "',1,'" . Connection::escape($user) . "')");
                                                if (strlen($codigoCliente) > 0) {
                                                    Connection::runQuery("REPLACE INTO `telefonos`( `clienteId`, `telefono`, `activo`)  VALUES ('" . $codigoCliente . "','" . $user . "',1)");
                                                    Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '" . $user . "'");
                                                    $this->client->sendText($user, 'Perfecto, ya te registramos!!');
                                                }
                                            } else {
                                                $this->client->sendText($user, "Hubo un error en la carga de la solicitud, disculpe 😔 \nPasos a seguir: \n1) Escribirnos nuevamente al chat. \n2) Elija la opción A.");
                                                Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '" . $user . "'");
                                                return;
                                            }
                                        } elseif ($this->tenantConfig['data']['b2b']) {
                                            $request = Connection::runQuery("SELECT menu,esperaRespuesta,anterior,mensaje FROM contactos where telefono = '" . $user . "'");
                                            if (mysqli_num_rows($request) > 0) {
                                                $row                   = mysqli_fetch_assoc($request);
                                                $anterior              = json_decode($row['anterior'], TRUE)['opcion'];
                                                $this->contactoMensaje = $row['mensaje'];
                                            }
                                            $porciones    = explode('_', $this->contactoMensaje);
                                            $location     = substr($porciones[3] ?? '', 1, -1);
                                            $partLocation = explode(',', $location);
                                            if ($porciones[1] != null || $porciones[1] != '') {
                                                $response = Connection::runQueryID("INSERT INTO `solicitudes`(`nombre`, `direccion`, `telefono`,`fecha`,`estado`,`latitud`,`longitud`) VALUES ('" . Connection::escape($porciones[1]) . "','" . Connection::escape($porciones[2]) . "','" . Connection::escape($user) . "',NOW(),0,'" . Connection::escape($partLocation[0] ?? '') . "','" . Connection::escape($partLocation[1] ?? '') . "')");
                                                if (strlen($response) > 0) {
                                                    Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '" . $user . "'");
                                                    $this->client->sendText($user, 'A la brevedad será informado el estado de su solicitud de cliente.');
                                                    return;
                                                }
                                            } else {
                                                $this->client->sendText($user, "Hubo un error en la carga de la solicitud, disculpe, reintente nuevamente😔");
                                                Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '" . $user . "'");
                                                return;
                                            }
                                        }
                                    }

                                    if ($menuItem[$j]['accion'] == 'confirmaPedidoFTP') {
                                        $row     = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '" . $codigoCliente . "'"));
                                        Connection::runQuery("UPDATE pedidos SET flag = 0 WHERE pedidoid like '" . $row['max'] . "'");
                                        $csv = '';
                                        $req = Connection::runQuery("SELECT pedidos.* ,  DATE_FORMAT( fecha,'%d-%m-%Y %H:%i:%s') as fechaEnviado, clientes.razonSocial,clientes.direccion,clientes.vendedor FROM `pedidos`, clientes where pedidos.clienteId= clientes.codigo and pedidos.flag =0 and pedidoid like '" . $row['max'] . "' order by fecha desc ,clienteId ASC");
                                        while ($row = mysqli_fetch_assoc($req)) {
                                            if ($row['producto'] == '.001') {
                                                $csv .= '"' . $row['pedidoid'] . '","' . $row['clienteId'] . '","' . $row['fechaEnviado'] . '","' . $row['producto'] . '","' . $row['cantidad'] . '","' . $row['pagado'] . '","' . $row['razonSocial'] . '","' . $row['direccion'] . '","' . $row['vendedo'] . '","","1","' . $row['descripcion'] . '"' . "\n";
                                            } else {
                                                $csv .= '"' . $row['pedidoid'] . '","' . $row['clienteId'] . '","' . $row['fechaEnviado'] . '","' . $row['producto'] . '","' . $row['cantidad'] . '","' . $row['pagado'] . '","' . $row['razonSocial'] . '","' . $row['direccion'] . '","' . $row['vendedo'] . '","","1","' . $row['dato9'] . '"' . "\n";
                                            }
                                            Connection::runQuery("UPDATE `pedidos` SET `flag`=1 where  id =" . $row['id']);
                                        }
                                        $filename = '../csv/pedidos/' . 'pedidos' . $codigoCliente . date_timestamp_get(date_create());
                                        $this->saveLog($csv, $filename);
                                    }

                                    if ($menuItem[$j]['accion'] == 'confirmaPedido') {
                                        $row     = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '" . $codigoCliente . "'"));
                                        Connection::runQuery("UPDATE pedidos SET flag = 0, pagado = 2 WHERE pedidoid like '" . $row['max'] . "'");
                                        $this->sendWap($codigoCliente, $row['max'], $user);
                                    }

                                    if ($menuItem[$j]['accion'] == 'confirmaTransferencia') {
                                        $row     = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '" . $codigoCliente . "'"));
                                        Connection::runQuery("UPDATE pedidos SET flag = 0, pagado = 3 WHERE pedidoid like '" . $row['max'] . "'");
                                        $this->sendWap($codigoCliente, $row['max'], $user);
                                    }

                                    if ($menuItem[$j]['accion'] == 'confirmaCuentaCorriente') {
                                        $row     = mysqli_fetch_array(Connection::runQuery("SELECT MAX(pedidoid) as max FROM pedidos where clienteId like '" . $codigoCliente . "'"));
                                        Connection::runQuery("UPDATE pedidos SET flag = 0, pagado = 4 WHERE pedidoid like '" . $row['max'] . "'");
                                        $this->sendWap($codigoCliente, $row['max'], $user);
                                    }

                                    if ($menuItem[$j]['accion'] == 'contactoVendedor') {
                                        $this->sendContacto($codigoCliente, $user);
                                    }

                                    if ($menuItem[$j]['accion'] == 'registrarConsulta') {
                                        $request = Connection::runQuery("SELECT anterior FROM contactos where telefono LIKE '" . $user . "'");
                                        if (mysqli_num_rows($request) > 0) {
                                            $row = mysqli_fetch_assoc($request);
                                            if (strlen($row['anterior']) > 0) {
                                                $_anterior = json_decode($row['anterior'], TRUE);
                                                $_area     = $_anterior['area'];
                                                $_motivo   = $_anterior['opcion'];
                                            }
                                        }

                                        $consultaSql = "INSERT INTO `consultas`(`empresa`,
                                                                                `fecha_ingreso`,
                                                                                `clienteId`,
                                                                                `telefono`,
                                                                                `nick`,
                                                                                `motivo`,
                                                                                `area`,
                                                                                `detalle`,
                                                                                `resolucion`)
                                                                                VALUES (        '" . $this->empresa . "',
                                                                                                now(),
                                                                                                '" . $codigoCliente . "',
                                                                                                '" . $user . "',
                                                                                                '" . $pushname . "',
                                                                                                '" . Connection::escape($_motivo) . "',
                                                                                                '" . $_area . "',
                                                                                                '" . $mensaje . "',
                                                                                                '')";
                                        $numeroReclamo  = '';
                                        $numeroConsulta = Connection::runQueryID($consultaSql);
                                        Connection::runQuery("UPDATE `contactos` SET `mensaje`= '', `anterior`= '', `esperaRespuesta`=0,`menu` = '0'  where id like '" . $user . "'");
                                        $request = Connection::runQuery("SELECT telefono FROM `areas_consultas` WHERE `id` = '" . $_area . "'");
                                        if (mysqli_num_rows($request) > 0) {
                                            $row            = mysqli_fetch_assoc($request);
                                            $telResponsable  = $row['telefono'];
                                            if (strlen($row['telefono']) > 0) {
                                                $request = null;
                                                if ($this->tenantConfig['data']['b2b']) {
                                                    $request = Connection::runQuery("SELECT *  FROM clientes WHERE  `codigo` =  '" . $codigoCliente . "'");
                                                } else {
                                                    $request = Connection::runQuery("SELECT *  FROM clientes WHERE  `id` = $codigoCliente");
                                                }

                                                if (mysqli_num_rows($request) > 0) {
                                                    $row        = mysqli_fetch_assoc($request);
                                                    $razonSocial = $row['razonSocial'];
                                                    $vendedor    = $row['vendedor'];
                                                    $direccion   = $row['direccion'];
                                                }

                                                $resultado  = 'Hay un nueva consulta de *' . $pushname . "* :\n";
                                                $resultado .= '*Consulta N°:* ' . $numeroConsulta . "\n" .
                                                    '*Cliente:* ' . $razonSocial . "\n" .
                                                    '*Dirección* ' . $direccion . "\n" .
                                                    '*Vendedor* ' . $vendedor . "\n" .
                                                    '*Motivo:* ' . $_motivo . "\n" .
                                                    '*Fecha:* ' . strftime('%Y-%m-%d %H:%M:%S', time()) . "\n" .
                                                    '*Tel:* ' . substr($user, 3) . "\n" .
                                                    '*Responder:* ' . tenantUrl($this->empresa, '/ws/m/movilc.php?id=' . $numeroConsulta) . "\n";
                                                $this->client->sendText(trim($telResponsable), $resultado);
                                            }
                                        }
                                    }
                                }
                            }

                            if (isset($menuItem[$j]['area'])) {
                                Connection::runQuery("UPDATE `contactos` SET `anterior`= '" . json_encode($menuItem[$j], JSON_UNESCAPED_UNICODE) . "' where id = '" . $user . "'");
                            }

                            if (strlen($numeroReclamo) > 0 && strlen($numeroConsulta) == 0 && $numeroReclamo != 0) {
                                $mensajePorcesado = $mensajePorcesado . 'Reclamo N°: *' . intval($numeroReclamo) . '*';
                            }
                            if (intval($numeroConsulta) > 0) {
                                // Mismo cierre que el flujo de reclamos: nº + "Nos estamos ocupando..."
                                $this->client->sendText($user, rtrim($mensajePorcesado) . 'Consulta N°: *' . intval($numeroConsulta) . '*');
                                $this->procesarAccion('3', '0', $mensaje, $pushname, $user, $codigoCliente);
                                return;
                            }
                            if (strlen($mensajePorcesado) > 0) {
                                $this->client->sendText($user, $mensajePorcesado);
                            }

                            $this->procesarAccion($menuItem[$j]['menuId'], '0', $mensaje, $pushname, $user, $codigoCliente);
                            $esOpcionValida = true;
                            break;
                        }
                    }
                }

                if (!$esOpcionValida) {
                    $this->procesarAccion('4', '0', $mensaje, $pushname, $user, $codigoCliente);
                    if ($menuItem[$j]['finaliza'] == 'true') {
                        $menu = '0';
                    }
                    $this->registrarContacto($pushname, $user, $menu, $espera_respuesta);
                }
            }
        }
    }

    private function consultarReclamo($reclamoId, $user)
    {
        $resultado = '';
        $request   = Connection::runQuery("SELECT *  FROM  reclamos WHERE   telefono  like '" . $user . "'  and  reclamoId=" . intval($reclamoId));

        if (mysqli_num_rows($request) > 0) {
            $row = mysqli_fetch_assoc($request);

            $resolucion = '';
            if ($row['resolucion'] == 'null') {
                $resolucion = $row['resolucion'];
            } else {
                $resolucion = $row['estado'];
            }

            $resultado  = '<saludo> *' . $row['nick'] . "* :\n";
            $resultado .= '*Reclamo N°:* ' . $row['reclamoId'] . "\n" .
                '*Motivo:* ' . $row['motivo'] . "\n" .
                '*Fecha:* ' . $row['fecha_ingreso'] . "\n" .
                '*Estado:* ' . $row['estado'] . "\n" .
                '*Resolucion:* ' . $row['resolucion'];
        }

        return $resultado;
    }

    private function registrarContacto($pushname, $user, $menu, $espera_respuesta)
    {
        Connection::runQuery("INSERT INTO `contactos`(`id`,`nombre`, `telefono`, `menu`, `esperaRespuesta`, `fechaHora`) VALUES ('" . $user . "','" . $pushname . "','" . $user . "','" . $menu . "','" . $espera_respuesta . "', now()) ON DUPLICATE KEY UPDATE nombre='" . $pushname . "' ,menu='" . $menu . "', esperaRespuesta='" . $espera_respuesta . "',fechaHora=now()");
    }

    private function getSaludo()
    {
        $hora   = date('H');
        $saludo = 'Buenas noches';

        if ($hora >= 6 && $hora <= 12) {
            $saludo = 'Buenos dias';
        }
        if ($hora > 12 && $hora <= 19) {
            $saludo = 'Buenas tardes';
        }
        if ($hora > 19 && $hora <= 24) {
            $saludo = 'Buenas noches ';
        }
        return $saludo;
    }

    private function buscarMenuClave($menuJson, $str)
    {
        $res = '';

        for ($i = 0; $i < count($menuJson); $i++) {
            if (isset($menuJson[$i]['palabraClave'])) {
                $arrayPalabras = $menuJson[$i]['palabraClave'];
                for ($j = 0; $j < count($arrayPalabras); $j++) {
                    if (preg_match('/' . $arrayPalabras[$j] . '/i', $str)) {
                        $res = $menuJson[$i]['menuId'];
                        $j   = count($arrayPalabras);
                    }
                }
            }
        }

        return $res;
    }

    private function sendWap($clienteID, $pedidoid, $tel)
    {
        $request  = Connection::runQuery("SELECT `vendedor` FROM `clientes` WHERE `codigo`  like '" . $clienteID . "'");
        $vendedor = '';
        if (mysqli_num_rows($request) > 0) {
            $row      = mysqli_fetch_assoc($request);
            $vendedor = $row['vendedor'];
        }

        $request  = Connection::runQuery("SELECT telefono FROM `vendedores` WHERE  codigo like '" . $vendedor . "'");
        $telefono = '';
        if (mysqli_num_rows($request) > 0) {
            $row      = mysqli_fetch_assoc($request);
            $telefono = $row['telefono'];
        }

        if (strlen($telefono) > 0) {
            $pedidos  = 'Fecha: ' . date('d/m/Y H:i:s') . "\n";
            $pedidos .= "------------------------------\n";
            $pedidos .= 'Cliente: ' . $clienteID . "\n";
            $pedidos .= "------------------------------\n";
            $pedidos .= 'Tel: ' . $tel . "\n";
            $pedidos .= "------------------------------\n";

            $request = Connection::runQuery("SELECT * FROM `pedidos` WHERE `pedidoid` like '" . $pedidoid . "'");
            $total   = 0;
            if ($request) {
                while ($row = mysqli_fetch_assoc($request)) {
                    $pedidos .= '```' . str_pad($row['producto'], 10, ' ') . '```' . $row['cantidad'] . "\n";
                    $total   = $total + floatval($row['subtotal']);
                }
            }
            $pedidos .= "------------------------------\n";
            $pedidos .= '*Total: $' . number_format($total, 2, '.', '') . "*\n";
            $pedidos .= "------------------------------\n";

            $this->client->sendText('549' . $telefono, $pedidos);
        }
    }

    private function sendContacto($clienteID, $tel)
    {
        $request     = Connection::runQuery("SELECT `vendedor`,razonSocial,direccion FROM `clientes` WHERE `codigo`  like '" . $clienteID . "'");
        $razonSocial = '';
        $direccion   = '';
        $vendedor    = '';
        if (mysqli_num_rows($request) > 0) {
            $row         = mysqli_fetch_assoc($request);
            $vendedor    = $row['vendedor'];
            $razonSocial = $row['razonSocial'];
            $direccion   = $row['direccion'];
        }

        $request    = Connection::runQuery("SELECT telefono,nombre FROM `vendedores` WHERE  codigo like '" . $vendedor . "'");
        $telefono   = '';
        $nombreVend = '';
        if (mysqli_num_rows($request) > 0) {
            $row        = mysqli_fetch_assoc($request);
            $telefono   = $row['telefono'];
            $nombreVend = $row['nombre'];
        }

        if (strlen($telefono) > 0) {
            $pedidos  = 'Fecha: ' . date('d/m/Y H:i:s') . "\n";
            $pedidos .= "------------------------------\n";
            $pedidos .= '*Cliente:* ' . $clienteID . "\n";
            $pedidos .= $razonSocial . "\n";
            $pedidos .= "------------------------------\n";
            $pedidos .= '*Domicilio:* ' . $direccion . "\n";
            $pedidos .= "------------------------------\n";
            $pedidos .= '*Tel:* ' . $tel . "\n";
            $pedidos .= "------------------------------\n";
            $pedidos .= 'El cliente necesita que te comuniques urgente... AX-BOT';

            $this->client->sendText('549' . $telefono, $pedidos);

            $pedidos  = "En breve el vendedor se comunicara\n";
            $pedidos .= '*Vendedor:* ' . $nombreVend . "\n";
            $this->client->sendText($tel, $pedidos);
        }
    }

    private function saveLog($data, $filename)
    {
        $fp = fopen($filename . '.txt', 'w+b');
        if ($fp == false) {
            // Error al crear el archivo
        } else {
            fwrite($fp, $data);
            fclose($fp);
        }
    }
}
