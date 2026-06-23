-- ============================================================
--  bot_config — menú del bot por tenant (una fila por DB atiende_*)
--  Aplicar dentro de la DB del tenant: mysql ... atiende_<slug> < bot_config_seed.sql
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `bot_config` (
  `id`                 tinyint(1)     NOT NULL DEFAULT 1,
  `menu_json`          longtext       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono`           varchar(20)    NULL DEFAULT NULL,
  `nombre_empresa`     varchar(255)   NULL DEFAULT NULL,
  `razon_social`       varchar(255)   NULL DEFAULT NULL,
  `cuit`               varchar(20)    NULL DEFAULT NULL,
  `logo`               varchar(255)   NULL DEFAULT NULL,
  `pais`               varchar(2)     NOT NULL DEFAULT 'AR',
  `costo_envio`        decimal(10,2)  NOT NULL DEFAULT 0.00,
  `costo_envio_activo` tinyint(1)     NOT NULL DEFAULT 0,
  `admin_telefono`     varchar(20)    NULL DEFAULT NULL,
  `admin_envio_activo` tinyint(1)     NOT NULL DEFAULT 0,
  `updated_at`         timestamp      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_single_row` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menú default (idempotente): inserta la fila 1 solo si no existe.
INSERT INTO `bot_config` (`id`, `menu_json`)
SELECT 1, '[{\"menuId\":\"0\",\"menuIdB\":\"100\",\"consigna\":\"\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"100\",\"consigna\":\"Bienvenido a *<empresa>*, *<nombre>*!!\\n\\nPara comenzar, elige una opción escribiendo solo el número:\\n\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"1\",\"opcion\":\"Ya soy Cliente\",\"menuId\":\"101\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"2\",\"opcion\":\"Quiero ser Cliente\",\"menuId\":\"102\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"3\",\"opcion\":\"No recuerdo mi número de cliente\",\"menuId\":\"103\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"recuperarCodigoCliente\"},{\"opcionId\":\"5\",\"opcion\":\"Soy Vendedor\",\"menuId\":\"105\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"4\",\"opcion\":\"Salir\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"101\",\"consigna\":\"Por favor ingresa tu *código de cliente* (figura en tu última factura):\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"200\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"registraNumero\"}]},{\"menuId\":\"103\",\"consigna\":\"Para registrarte enviá en *un solo mensaje*:\\n*Nombre/Razón Social:*\\n*Localidad:*\\n*Dirección/Dirección del negocio:*\\n*DNI o CUIL:*\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"3\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"registrarOlvidoReclamo\"}]},{\"menuId\":\"104\",\"consigna\":\"¡Gracias *<nombre>*! Registramos tu solicitud.\\n\\nUn agente se va a comunicar a la brevedad para ayudarte a identificar tu cuenta.\",\"finaliza\":\"true\",\"menuItem\":[]},{\"menuId\":\"102\",\"consigna\":\"¿Cuál es tu *nombre completo*?\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"8.1\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"8.1\",\"consigna\":\"Ahora escribí tu *dirección completa*.\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"802\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"802\",\"consigna\":\"Por último, compartí tu ubicación desde WhatsApp.\\n\\n📎 Adjuntar › Ubicación › Enviar mi ubicación actual\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"8.2\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"8.2\",\"consigna\":\"¡Ya tenemos tus datos! Enviá *SI* para confirmar el alta.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"3\",\"guardar\":\"false\",\"accion\":\"registraClientes\"}]},{\"menuId\":\"200\",\"consigna\":\"<saludo> *<nombre>*! ¿En qué podemos ayudarte?\\n\\nElegí una opción ingresando solo el número:\\n\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"1\",\"opcion\":\"Hacer un pedido\",\"menuId\":\"300\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"2\",\"opcion\":\"Hacer un reclamo\",\"menuId\":\"1\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"3\",\"opcion\":\"Consultar reclamo\",\"menuId\":\"400\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"4\",\"opcion\":\"Salir\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"1\",\"consigna\":\"Tu reclamo es sobre:\\n\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"5\",\"consigna\":\"Escribí el *detalle* de tu reclamo:\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"3\",\"guardar\":\"true\",\"area\":\"\"}]},{\"menuId\":\"300\",\"consigna\":\"Te enviaremos el link para tu pedido. <linkPedidos>\",\"finaliza\":\"true\",\"palabraClave\":[\"pedido\",\"comprar\"],\"menuItem\":[]},{\"menuId\":\"400\",\"consigna\":\"Ingresa el número de reclamo a consultar:\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"consultarReclamo\"}]},{\"menuId\":\"3\",\"consigna\":\"Nos estamos ocupando de inmediato.\\n*Gracias* por tu contacto.\",\"finaliza\":\"true\",\"menuItem\":[]},{\"menuId\":\"2.2\",\"consigna\":\"Gracias *<nombre>*. ¡Hasta pronto!\",\"finaliza\":\"true\",\"menuItem\":[]},{\"menuId\":\"4\",\"consigna\":\"*Upps!!* Ingresaste una opción no válida, intenta nuevamente.\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"105\",\"consigna\":\"Ingresá tu *código de vendedor*:\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"106\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"registraVendedor\"}]},{\"menuId\":\"106\",\"consigna\":\"Ingresá el *código del cliente* al que vas a cargar el pedido.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"300\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"chequearVendedorCliente\"}]}]'
WHERE NOT EXISTS (SELECT 1 FROM `bot_config` WHERE `id` = 1);

-- ============================================================
--  motivo_reclamos — fila reservada del flujo "recuperar código"
--  opcionId '99' (numérico, fuera del rango visible), area '0' (sin área
--  por default; el admin se la asigna desde el panel). BotEngine la SALTEA
--  al inyectar los motivos en el menuId 1, así nunca aparece como opción
--  seleccionable al hacer un reclamo normal. El flujo forgot-code lee su
--  `area` para el reclamo y la cascada de aviso.
--  La tabla se define en atiende.sql; el CREATE IF NOT EXISTS la deja
--  presente también si se aplica este seed a un tenant que aún no la tiene.
-- ============================================================
CREATE TABLE IF NOT EXISTS `motivo_reclamos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opcionId` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `opcion` varchar(250) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `menuId` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `guardar` tinyint(1) NOT NULL,
  `area` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_spanish_ci ROW_FORMAT = Dynamic;

-- Fila reservada (idempotente): se inserta solo si no existe ya el opcionId '99'.
-- estado 0 → no se lista en el panel admin; el bot la usa igual.
INSERT INTO `motivo_reclamos` (`opcionId`, `opcion`, `menuId`, `guardar`, `area`, `estado`)
SELECT '99', 'No recuerdo mi numero de cliente', '5', 0, '0', 0
WHERE NOT EXISTS (SELECT 1 FROM `motivo_reclamos` WHERE `opcionId` = '99');
