<?php
/**
 * Política de Privacidad — página pública (sin login).
 *
 * Cumple con la Ley N° 25.326 de Protección de los Datos Personales,
 * su Decreto reglamentario 1558/2001 y las disposiciones de la
 * Agencia de Acceso a la Información Pública (AAIP), autoridad de
 * aplicación en la República Argentina.
 *
 * Reemplazá los placeholders [RAZÓN SOCIAL], [DOMICILIO], [CUIT],
 * [EMAIL], [LOCALIDAD, PROVINCIA] por los datos reales del responsable
 * de la base de datos antes de publicar.
 */

// Fecha de última actualización (editar manualmente al modificar el texto)
$ultimaActualizacion = '02 de junio de 2026';

// Datos del responsable — completar con los reales o sobreescribir por tenant.
$RESPONSABLE = '[RAZÓN SOCIAL]';
$CUIT        = '[CUIT]';
$DOMICILIO   = '[DOMICILIO, LOCALIDAD, PROVINCIA]';
$EMAIL       = '[EMAIL DE CONTACTO]';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Política de Privacidad y Protección de Datos Personales conforme a la Ley 25.326 de la República Argentina.">
    <title>Política de Privacidad | Atiende</title>
    <link href="public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="public/bs5/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/icons.min.css">
    <script src="public/bs5/bootstrap.bundle.min.js"></script>
    <style>
        :root { --ap: #6c63ff; --am: #3cd4ac; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f2f5; color: #333; }
        .navbar-atiende { background: var(--ap) !important; min-height: 56px; }
        .navbar-atiende .navbar-brand { color: #fff; font-weight: 700; letter-spacing: .3px; }
        .pp-hero { background: var(--ap); color: #fff; padding: 40px 0 56px; }
        .pp-hero h1 { font-weight: 800; letter-spacing: -.5px; margin-bottom: 6px; }
        .pp-hero p { color: rgba(255,255,255,.85); margin: 0; font-size: .95rem; }
        .pp-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); margin-top: -32px; padding: 32px 36px; }
        .pp-card h2 { font-size: 1.15rem; font-weight: 700; color: var(--ap); margin-top: 30px; margin-bottom: 12px; padding-top: 6px; }
        .pp-card h2:first-of-type { margin-top: 8px; }
        .pp-card h3 { font-size: 1rem; font-weight: 700; color: #444; margin-top: 18px; margin-bottom: 8px; }
        .pp-card p, .pp-card li { font-size: .92rem; line-height: 1.7; color: #444; }
        .pp-card ul { padding-left: 1.25rem; }
        .pp-card li { margin-bottom: 6px; }
        .pp-card a { color: var(--ap); }
        .pp-num { display: inline-block; min-width: 26px; color: var(--ap); font-weight: 800; }
        .pp-update { font-size: .8rem; color: #888; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
        .pp-callout { background: #f8f7ff; border-left: 4px solid var(--ap); border-radius: 8px; padding: 14px 18px; font-size: .9rem; margin: 16px 0; }
        .pp-toc { background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 18px 22px; margin-bottom: 24px; }
        .pp-toc ol { margin: 0; padding-left: 1.2rem; }
        .pp-toc li { font-size: .88rem; margin-bottom: 4px; }
        .pp-toc a { text-decoration: none; }
        .pp-toc a:hover { text-decoration: underline; }
        .pp-data-table { width: 100%; font-size: .88rem; border-collapse: collapse; margin: 12px 0; }
        .pp-data-table th { background: #f3f2ff; color: #555; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; padding: 8px 10px; }
        .pp-data-table td { border-top: 1px solid #eee; padding: 8px 10px; vertical-align: top; }
        footer.pp-footer { background: #2a2a3a; color: rgba(255,255,255,.7); font-size: .82rem; padding: 24px 0; margin-top: 40px; }
        footer.pp-footer a { color: rgba(255,255,255,.9); }
        @media (max-width: 575px) { .pp-card { padding: 24px 18px; } }
    </style>
</head>
<body>

<nav class="navbar navbar-atiende">
    <div class="container">
        <a class="navbar-brand" href="index.php">Atiende</a>
    </div>
</nav>

<div class="pp-hero">
    <div class="container">
        <h1>Política de Privacidad</h1>
        <p>Protección de Datos Personales — Ley N° 25.326 (República Argentina)</p>
    </div>
</div>

<div class="container" style="max-width: 880px;">
    <div class="pp-card">
        <p class="pp-update">Última actualización: <?php echo htmlspecialchars($ultimaActualizacion); ?></p>

        <p>
            La presente Política de Privacidad describe cómo <strong><?php echo htmlspecialchars($RESPONSABLE); ?></strong>
            (en adelante, &laquo;el Responsable&raquo;), titular de la plataforma <strong>Atiende</strong>, recolecta,
            utiliza, almacena y protege los datos personales de los usuarios y clientes, en cumplimiento de la
            <strong>Ley N° 25.326 de Protección de los Datos Personales</strong>, su Decreto reglamentario
            N° 1558/2001 y las disposiciones de la <strong>Agencia de Acceso a la Información Pública (AAIP)</strong>,
            autoridad de aplicación en la República Argentina.
        </p>

        <div class="pp-toc">
            <strong style="font-size:.85rem; color:#555;">Contenido</strong>
            <ol>
                <li><a href="#responsable">Responsable de la base de datos</a></li>
                <li><a href="#datos">Datos personales que recolectamos</a></li>
                <li><a href="#finalidad">Finalidad del tratamiento</a></li>
                <li><a href="#consentimiento">Consentimiento</a></li>
                <li><a href="#whatsapp">Datos a través de WhatsApp</a></li>
                <li><a href="#pagos">Datos de pago</a></li>
                <li><a href="#cesion">Cesión y transferencia de datos</a></li>
                <li><a href="#internacional">Transferencia internacional de datos</a></li>
                <li><a href="#conservacion">Conservación de los datos</a></li>
                <li><a href="#seguridad">Medidas de seguridad</a></li>
                <li><a href="#derechos">Derechos del titular de los datos</a></li>
                <li><a href="#menores">Datos de menores de edad</a></li>
                <li><a href="#cookies">Cookies y tecnologías similares</a></li>
                <li><a href="#aaip">Autoridad de control (AAIP)</a></li>
                <li><a href="#cambios">Cambios en esta política</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ol>
        </div>

        <h2 id="responsable"><span class="pp-num">1.</span> Responsable de la base de datos</h2>
        <p>El responsable del tratamiento de los datos personales es:</p>
        <ul>
            <li><strong>Razón social:</strong> <?php echo htmlspecialchars($RESPONSABLE); ?></li>
            <li><strong>CUIT:</strong> <?php echo htmlspecialchars($CUIT); ?></li>
            <li><strong>Domicilio:</strong> <?php echo htmlspecialchars($DOMICILIO); ?></li>
            <li><strong>Correo electrónico de contacto:</strong> <a href="mailto:<?php echo htmlspecialchars($EMAIL); ?>"><?php echo htmlspecialchars($EMAIL); ?></a></li>
        </ul>
        <div class="pp-callout">
            Conforme al artículo 21 de la Ley N° 25.326, toda base de datos destinada a proporcionar informes
            debe inscribirse en el <strong>Registro Nacional de Bases de Datos</strong> de la AAIP. El Responsable
            mantiene su base de datos debidamente registrada.
        </div>

        <h2 id="datos"><span class="pp-num">2.</span> Datos personales que recolectamos</h2>
        <p>En el marco de la prestación del servicio, podemos recolectar y tratar las siguientes categorías de datos:</p>
        <table class="pp-data-table">
            <thead>
                <tr><th>Categoría</th><th>Datos incluidos</th><th>Origen</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Datos de identificación y contacto</td>
                    <td>Nombre y apellido, número de teléfono, dirección de correo electrónico, domicilio de entrega.</td>
                    <td>Provistos por el titular.</td>
                </tr>
                <tr>
                    <td>Datos de mensajería (WhatsApp)</td>
                    <td>Número de teléfono, contenido de los mensajes y conversaciones intercambiadas, fecha y hora.</td>
                    <td>WhatsApp / Meta Platforms.</td>
                </tr>
                <tr>
                    <td>Datos de pedidos y reclamos</td>
                    <td>Productos solicitados, cantidades, observaciones, historial de pedidos, consultas y reclamos.</td>
                    <td>Generados durante el uso del servicio.</td>
                </tr>
                <tr>
                    <td>Datos de pago</td>
                    <td>Estado e identificador de la transacción. <strong>No almacenamos datos de tarjetas.</strong></td>
                    <td>Procesador de pagos (MercadoPago).</td>
                </tr>
                <tr>
                    <td>Datos técnicos</td>
                    <td>Dirección IP, tipo de dispositivo y navegador, registros de actividad (logs).</td>
                    <td>Recolectados automáticamente.</td>
                </tr>
            </tbody>
        </table>
        <p>
            No recolectamos de forma intencional <strong>datos sensibles</strong> en los términos del artículo 2 de la
            Ley N° 25.326 (origen racial o étnico, opiniones políticas, convicciones religiosas, información referente
            a la salud o a la vida sexual). En caso de que resultara necesario tratar este tipo de datos, se solicitará
            el consentimiento expreso, libre, escrito e informado del titular.
        </p>

        <h2 id="finalidad"><span class="pp-num">3.</span> Finalidad del tratamiento</h2>
        <p>Los datos personales se tratan con las siguientes finalidades:</p>
        <ul>
            <li>Gestionar y procesar pedidos, consultas y reclamos.</li>
            <li>Comunicarnos con el cliente a través de WhatsApp y otros canales para coordinar el servicio.</li>
            <li>Procesar pagos de los pedidos realizados.</li>
            <li>Brindar soporte y atención al cliente.</li>
            <li>Cumplir con obligaciones legales, fiscales y contables.</li>
            <li>Mejorar la calidad del servicio y elaborar estadísticas internas (de forma agregada).</li>
        </ul>
        <p>
            Los datos no serán utilizados para finalidades distintas o incompatibles con aquellas que motivaron su
            obtención, conforme al principio de finalidad establecido en el artículo 4 de la Ley N° 25.326.
        </p>

        <h2 id="consentimiento"><span class="pp-num">4.</span> Consentimiento</h2>
        <p>
            El tratamiento de datos personales requiere el consentimiento libre, expreso e informado del titular
            (artículo 5 de la Ley N° 25.326). Al utilizar el servicio, iniciar una conversación por WhatsApp o
            realizar un pedido, el titular presta su consentimiento para el tratamiento de sus datos conforme a
            esta Política. El consentimiento puede ser revocado en cualquier momento, sin efecto retroactivo.
        </p>

        <h2 id="whatsapp"><span class="pp-num">5.</span> Datos a través de WhatsApp</h2>
        <p>
            Atiende integra la mensajería de <strong>WhatsApp</strong> (servicio provisto por Meta Platforms, Inc.)
            para gestionar la comunicación con los clientes. Al escribirnos por WhatsApp, el titular comparte su
            número de teléfono y el contenido de los mensajes, que se almacenan para gestionar pedidos, consultas y
            reclamos, y para mantener el historial de la conversación.
        </p>
        <p>
            El uso de WhatsApp se rige adicionalmente por la
            <a href="https://www.whatsapp.com/legal/privacy-policy" target="_blank" rel="noopener">Política de Privacidad de WhatsApp/Meta</a>,
            sobre la cual el Responsable no tiene control. Recomendamos su lectura.
        </p>

        <h2 id="pagos"><span class="pp-num">6.</span> Datos de pago</h2>
        <p>
            Los pagos en línea se procesan a través de <strong>MercadoPago</strong>, un proveedor externo de servicios
            de pago. Los datos de tarjetas de crédito/débito y demás credenciales financieras son ingresados y tratados
            directamente por dicho proveedor en sus propios entornos seguros; <strong>Atiende no almacena ni tiene acceso
            a los datos completos de las tarjetas</strong>. Únicamente conservamos el estado y el identificador de la
            transacción para conciliar el pedido. El tratamiento de estos datos por parte del procesador se rige por su
            propia política de privacidad.
        </p>

        <h2 id="cesion"><span class="pp-num">7.</span> Cesión y transferencia de datos</h2>
        <p>
            Los datos personales podrán ser cedidos o comunicados a terceros únicamente cuando ello sea necesario para
            la prestación del servicio (por ejemplo, proveedores de mensajería, procesadores de pago o servicios de
            infraestructura tecnológica), o cuando exista una obligación legal o un requerimiento de autoridad
            competente. En todos los casos, el cesionario queda sujeto a las mismas obligaciones de confidencialidad y
            seguridad que el Responsable (artículos 11 y 12 de la Ley N° 25.326).
        </p>

        <h2 id="internacional"><span class="pp-num">8.</span> Transferencia internacional de datos</h2>
        <p>
            Algunos de los proveedores que intervienen en el servicio (como Meta/WhatsApp) pueden almacenar o procesar
            datos fuera de la República Argentina. Estas transferencias se realizan conforme al artículo 12 de la Ley
            N° 25.326 y a las disposiciones de la AAIP en la materia, garantizando niveles de protección adecuados
            mediante cláusulas contractuales y resguardos apropiados.
        </p>

        <h2 id="conservacion"><span class="pp-num">9.</span> Conservación de los datos</h2>
        <p>
            Los datos personales se conservan mientras sean necesarios para las finalidades para las que fueron
            recolectados y, posteriormente, durante los plazos exigidos por la legislación fiscal, contable y de
            defensa del consumidor. Cumplidos dichos plazos, los datos serán suprimidos o anonimizados conforme al
            artículo 4, inciso 7, de la Ley N° 25.326.
        </p>

        <h2 id="seguridad"><span class="pp-num">10.</span> Medidas de seguridad</h2>
        <p>
            El Responsable adopta las medidas técnicas y organizativas necesarias para garantizar la seguridad y
            confidencialidad de los datos personales, evitando su adulteración, pérdida, consulta o tratamiento no
            autorizado (artículo 9 de la Ley N° 25.326). Entre otras medidas se incluyen el control de acceso por
            usuario y contraseña, la segmentación de la información por cliente, el cifrado de las comunicaciones y
            el registro de la actividad. Ninguna medida garantiza seguridad absoluta, pero trabajamos para reducir
            los riesgos de manera razonable.
        </p>

        <h2 id="derechos"><span class="pp-num">11.</span> Derechos del titular de los datos</h2>
        <p>
            Como titular de los datos personales, usted tiene derecho a ejercer, en forma gratuita, los siguientes
            derechos (artículos 14 a 16 de la Ley N° 25.326):
        </p>
        <ul>
            <li><strong>Acceso:</strong> conocer qué datos suyos tratamos y con qué finalidad.</li>
            <li><strong>Rectificación:</strong> corregir datos inexactos o incompletos.</li>
            <li><strong>Actualización:</strong> mantener sus datos al día.</li>
            <li><strong>Supresión:</strong> solicitar la eliminación de sus datos cuando corresponda.</li>
            <li><strong>Confidencialidad y oposición:</strong> oponerse al tratamiento en los casos previstos por la ley.</li>
        </ul>
        <div class="pp-callout">
            Para ejercer estos derechos, envíe su solicitud a
            <a href="mailto:<?php echo htmlspecialchars($EMAIL); ?>"><?php echo htmlspecialchars($EMAIL); ?></a>,
            acreditando su identidad. El derecho de acceso podrá ejercerse en forma gratuita a intervalos no inferiores
            a seis meses, salvo que se acredite un interés legítimo. El Responsable responderá dentro de los plazos
            legales (10 días corridos para el acceso; 5 días hábiles para rectificación o supresión).
        </div>
        <div class="pp-callout">
            <strong>Eliminación de datos:</strong> si querés que eliminemos tus datos personales, podés conocer el
            procedimiento paso a paso y solicitarlo desde nuestra página de
            <a href="eliminar-datos.php">Eliminación de tus datos personales</a>.
        </div>
        <p>
            <strong>Datos del titular del derecho a la información de la AAIP:</strong> &laquo;El titular de los datos
            personales tiene la facultad de ejercer el derecho de acceso a los mismos en forma gratuita a intervalos
            no inferiores a seis meses, salvo que se acredite un interés legítimo al efecto conforme lo establecido en
            el artículo 14, inciso 3 de la Ley N° 25.326.&raquo;
        </p>
        <p>
            &laquo;La AGENCIA DE ACCESO A LA INFORMACIÓN PÚBLICA, en su carácter de Órgano de Control de la Ley
            N° 25.326, tiene la atribución de atender las denuncias y reclamos que interpongan quienes resulten
            afectados en sus derechos por incumplimiento de las normas vigentes en materia de protección de datos
            personales.&raquo;
        </p>

        <h2 id="menores"><span class="pp-num">12.</span> Datos de menores de edad</h2>
        <p>
            El servicio está dirigido a personas mayores de edad. No recolectamos de forma intencional datos de
            menores de edad sin el consentimiento de sus padres, tutores o representantes legales. Si tomamos
            conocimiento de que hemos tratado datos de un menor sin la debida autorización, procederemos a su
            supresión.
        </p>

        <h2 id="cookies"><span class="pp-num">13.</span> Cookies y tecnologías similares</h2>
        <p>
            La plataforma puede utilizar cookies técnicas y de sesión necesarias para su funcionamiento (por ejemplo,
            para mantener la sesión iniciada). El usuario puede configurar su navegador para rechazar las cookies,
            aunque ello podría afectar el correcto funcionamiento de algunas funcionalidades.
        </p>

        <h2 id="aaip"><span class="pp-num">14.</span> Autoridad de control (AAIP)</h2>
        <p>
            La <strong>Agencia de Acceso a la Información Pública (AAIP)</strong> es el organismo de control de la Ley
            N° 25.326. El titular de los datos puede presentar denuncias o reclamos ante dicha autoridad cuando
            considere vulnerados sus derechos. Más información en
            <a href="https://www.argentina.gob.ar/aaip" target="_blank" rel="noopener">www.argentina.gob.ar/aaip</a>.
        </p>

        <h2 id="cambios"><span class="pp-num">15.</span> Cambios en esta política</h2>
        <p>
            El Responsable podrá actualizar esta Política de Privacidad en cualquier momento para reflejar cambios
            legales, técnicos u operativos. La versión vigente será siempre la publicada en esta página, con su
            fecha de última actualización. Recomendamos revisarla periódicamente.
        </p>

        <h2 id="contacto"><span class="pp-num">16.</span> Contacto</h2>
        <p>
            Ante cualquier consulta relacionada con esta Política de Privacidad o con el tratamiento de sus datos
            personales, puede comunicarse con nosotros a través de:
        </p>
        <ul>
            <li><strong>Correo electrónico:</strong> <a href="mailto:<?php echo htmlspecialchars($EMAIL); ?>"><?php echo htmlspecialchars($EMAIL); ?></a></li>
            <li><strong>Domicilio:</strong> <?php echo htmlspecialchars($DOMICILIO); ?></li>
        </ul>
    </div>
</div>

<footer class="pp-footer">
    <div class="container" style="max-width: 880px;">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>&copy; <?php echo date('Y'); ?> Atiende. Todos los derechos reservados.</span>
            <a href="index.php">Volver al inicio</a>
        </div>
    </div>
</footer>

</body>
</html>
