<?php
/**
 * Eliminación de Datos de Usuario — página pública de instrucciones (H1).
 *
 * Página GENÉRICA de Atiende (plataforma). NO muestra datos por tenant.
 * Es la URL que figura como "Data Deletion Instructions" en el Meta Developer Portal.
 *
 * Cumple:
 *  - Requisito de Meta/WhatsApp: instrucciones públicas de eliminación de datos.
 *  - Ley N° 25.326 (Argentina): derecho de supresión del titular de los datos.
 *
 * Decisiones cerradas (spec 2026-06-02 §13):
 *  - Responsable ante la AAIP = Atiende (plataforma), NO por tenant.
 *  - SLA = 10 días hábiles.
 *  - Política = anonimización irreversible (no borrado físico); solo se conservan
 *    registros transaccionales sin PII por obligación legal/fiscal.
 *
 * SEGURIDAD (spec §15):
 *  - Página estática y genérica: NO recibe ni refleja ?t=<slug>.
 *  - NO inicia sesión ni escribe $_SESSION['tenant_db'] (anti session-fixation / IDOR).
 *  - Cualquier salida dinámica usa htmlspecialchars(..., ENT_QUOTES, 'UTF-8').
 *
 * Reemplazá los placeholders [RAZÓN SOCIAL] (entidad legal de Atiende) y
 * [EMAIL] (correo de privacidad de Atiende) por los datos reales antes de publicar.
 */

// Datos del responsable de la plataforma (Atiende). Texto único para todos los tenants.
$RESPONSABLE = '[RAZÓN SOCIAL]';
$EMAIL       = '[EMAIL]';

// SLA fijo de resolución (decisión §13.2).
$SLA = '10 días hábiles';

// Helper local de escape (la página no toma ningún input del usuario, pero todo
// echo de variable pasa por acá por convención de seguridad del proyecto).
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Instrucciones para solicitar la eliminación de tus datos personales en Atiende, conforme a la Ley 25.326 y a las políticas de WhatsApp/Meta.">
    <title>Eliminación de tus datos personales | Atiende</title>
    <link href="public/img/logo30x30.png" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="public/bs5/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/icons.min.css">
    <script src="public/bs5/bootstrap.bundle.min.js"></script>
    <style>
        :root { --ap: #6c63ff; --am: #3cd4ac; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f2f5; color: #333; }
        .navbar-atiende { background: var(--ap) !important; min-height: 56px; }
        .navbar-atiende .navbar-brand { color: #fff; font-weight: 700; letter-spacing: .3px; }
        .navbar-atiende .nav-link { color: rgba(255,255,255,.9); font-size: .9rem; }
        .navbar-atiende .nav-link:hover { color: #fff; }
        .pp-hero { background: var(--ap); color: #fff; padding: 40px 0 56px; }
        .pp-hero h1 { font-weight: 800; letter-spacing: -.5px; margin-bottom: 6px; }
        .pp-hero p { color: rgba(255,255,255,.85); margin: 0; font-size: .95rem; }
        .pp-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); margin-top: -32px; padding: 32px 36px; }
        .pp-card h2 { font-size: 1.15rem; font-weight: 700; color: var(--ap); margin-top: 30px; margin-bottom: 12px; padding-top: 6px; }
        .pp-card h2:first-of-type { margin-top: 8px; }
        .pp-card h3 { font-size: 1rem; font-weight: 700; color: #444; margin-top: 18px; margin-bottom: 8px; }
        .pp-card p, .pp-card li { font-size: .92rem; line-height: 1.7; color: #444; }
        .pp-card ul, .pp-card ol { padding-left: 1.25rem; }
        .pp-card li { margin-bottom: 6px; }
        .pp-card a { color: var(--ap); }
        .pp-steps { list-style: none; padding-left: 0; counter-reset: step; }
        .pp-steps > li { position: relative; padding-left: 46px; margin-bottom: 18px; }
        .pp-steps > li::before {
            counter-increment: step; content: counter(step);
            position: absolute; left: 0; top: 0;
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--ap); color: #fff; font-weight: 700;
            display: flex; align-items: center; justify-content: center; font-size: .9rem;
        }
        .pp-steps > li strong { display: block; color: #333; margin-bottom: 2px; }
        .pp-callout { background: #f8f7ff; border-left: 4px solid var(--ap); border-radius: 8px; padding: 14px 18px; font-size: .9rem; margin: 16px 0; }
        .pp-callout.warn { background: #fff8f0; border-left-color: #f7b84b; }
        .pp-data-table { width: 100%; font-size: .88rem; border-collapse: collapse; margin: 12px 0; }
        .pp-data-table th { background: #f3f2ff; color: #555; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; padding: 8px 10px; }
        .pp-data-table td { border-top: 1px solid #eee; padding: 8px 10px; vertical-align: top; }
        .pp-badge { display: inline-block; background: #f3f2ff; color: var(--ap); border-radius: 999px; padding: 3px 12px; font-size: .8rem; font-weight: 700; }
        .pp-soon { display: inline-block; background: #eef0f3; color: #888; border-radius: 6px; padding: 8px 14px; font-size: .85rem; font-weight: 600; }
        footer.pp-footer { background: #2a2a3a; color: rgba(255,255,255,.7); font-size: .82rem; padding: 24px 0; margin-top: 40px; }
        footer.pp-footer a { color: rgba(255,255,255,.9); }
        @media (max-width: 575px) { .pp-card { padding: 24px 18px; } }
    </style>
</head>
<body>

<nav class="navbar navbar-atiende navbar-expand-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">Atiende</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a class="nav-link" href="politica-privacidad.php">Política de Privacidad &rsaquo;</a>
            </li>
        </ul>
    </div>
</nav>

<div class="pp-hero">
    <div class="container">
        <h1>Eliminación de tus datos personales</h1>
        <p>Cómo pedir que eliminemos tus datos — Ley N° 25.326 y políticas de WhatsApp/Meta</p>
    </div>
</div>

<div class="container" style="max-width: 880px;">
    <div class="pp-card">

        <p>
            Si interactuaste a través de WhatsApp con un comercio que usa <strong>Atiende</strong>, podés
            pedirnos que eliminemos los datos personales que guardamos sobre vos. En esta página te explicamos
            qué datos almacenamos y cómo solicitar su eliminación de forma simple y segura.
        </p>
        <p>
            <strong>Atiende</strong> (operada por <strong><?php echo e($RESPONSABLE); ?></strong>) es la
            responsable de la base de datos donde se almacena tu información. Los comercios que usan la
            plataforma actúan como encargados del tratamiento. Por eso podés ejercer tu derecho de eliminación
            directamente con nosotros, desde esta página. Este derecho te lo reconoce la
            <strong>Ley N° 25.326 de Protección de los Datos Personales</strong> de la República Argentina,
            y forma parte de nuestro compromiso de cumplimiento con las políticas de <strong>WhatsApp / Meta</strong>.
        </p>

        <div class="pp-callout">
            <strong>Responsable de los datos:</strong> Atiende (<?php echo e($RESPONSABLE); ?>) &middot;
            <a href="mailto:<?php echo e($EMAIL); ?>"><?php echo e($EMAIL); ?></a><br>
            <strong>Plazo de resolución:</strong> hasta <?php echo e($SLA); ?>.<br>
            <span class="text-muted" style="font-size:.85rem;">Página única de Atiende — sin datos por comercio.</span>
        </div>

        <h2>¿Qué datos almacenamos sobre vos?</h2>
        <p>Cuando usás nuestro servicio o nos escribís por WhatsApp, podemos guardar:</p>
        <ul>
            <li><strong>Nombre o razón social</strong> que nos indicaste.</li>
            <li><strong>Número de teléfono</strong> de WhatsApp.</li>
            <li><strong>Dirección</strong> de entrega y localidad.</li>
            <li><strong>Correo electrónico</strong> (si lo proporcionaste).</li>
            <li><strong>Historial de pedidos</strong>: productos, cantidades y observaciones.</li>
            <li><strong>Reclamos y consultas</strong> que nos hayas hecho.</li>
            <li><strong>Conversaciones de WhatsApp</strong>: el contenido de los mensajes que intercambiamos, con su fecha y hora.</li>
        </ul>
        <p>
            Si querés conocer en detalle qué datos tratamos y con qué finalidad, podés leer nuestra
            <a href="politica-privacidad.php">Política de Privacidad</a>.
        </p>

        <h2>¿Qué pasa cuando solicitás la eliminación?</h2>
        <p>
            <strong>Anonimizamos de forma irreversible</strong> todos los datos personales asociados a tu número
            de WhatsApp: nombre, teléfono, dirección, correo electrónico y cualquier otro dato que te identifique.
            Una vez completado el proceso, <strong>no conservamos ningún dato que permita identificarte ni
            vincularte con tu actividad</strong>.
        </p>
        <p>
            Por obligación legal (fiscal y contable) podemos conservar <strong>registros transaccionales</strong>
            —por ejemplo, que existió un pedido por cierto importe en cierta fecha—, pero <strong>sin ningún dato
            personal tuyo</strong>: quedan disociados de tu identidad y no pueden volver a asociarse a vos.
        </p>
        <div class="pp-callout warn">
            <strong>Importante:</strong> la anonimización es definitiva e irreversible. Una vez completada, no
            podremos recuperar tu historial de pedidos, reclamos ni conversaciones, ni reconstruir tus datos.
        </div>

        <h2>Cómo solicitar la eliminación — paso a paso</h2>

        <h3>Opción 1: contacto por WhatsApp o correo electrónico</h3>
        <p>
            Para solicitar la eliminación de tus datos, escribinos a
            <a href="mailto:<?php echo e($EMAIL); ?>"><?php echo e($EMAIL); ?></a> indicando que querés eliminar
            tus datos personales, o comunicate por el mismo WhatsApp por el que nos contactaste. Para proteger tu
            privacidad, vamos a pedirte que <strong>acredites tu identidad</strong> antes de procesar la solicitud.
        </p>
        <ol class="pp-steps">
            <li>
                <strong>Contactanos</strong>
                Escribinos a <a href="mailto:<?php echo e($EMAIL); ?>"><?php echo e($EMAIL); ?></a> o por
                WhatsApp, indicando el número con el que interactuaste con nosotros.
            </li>
            <li>
                <strong>Verificamos tu identidad</strong>
                Te pediremos que confirmes que sos el titular del número, para asegurarnos de que el pedido lo
                hacés vos y no otra persona.
            </li>
            <li>
                <strong>Registramos tu solicitud</strong>
                Confirmada tu identidad, registramos el pedido de eliminación y comenzamos a anonimizar tus datos.
            </li>
            <li>
                <strong>Te confirmamos cuando esté lista</strong>
                Te avisamos cuando la eliminación quede <strong>completada</strong>. El plazo de resolución es de
                hasta <?php echo e($SLA); ?> desde que verificamos tu identidad.
            </li>
        </ol>

        <!-- TODO H3: formulario de autogestión con OTP -->
        <h3>Opción 2: formulario en línea (próximamente)</h3>
        <p>
            Estamos habilitando un formulario en línea para que puedas solicitar la eliminación vos mismo:
            ingresás tu número de WhatsApp, te enviamos un <strong>código de verificación de 6 dígitos</strong>
            por WhatsApp, lo confirmás y obtenés un <strong>código de seguimiento</strong> para consultar el
            estado de tu solicitud sin volver a ingresar tus datos personales.
        </p>
        <p><span class="pp-soon">Formulario en línea — próximamente</span></p>
        <p class="text-muted" style="font-size:.85rem;">
            Mientras tanto, podés usar la <strong>Opción 1</strong> (WhatsApp o correo electrónico) para
            solicitar la eliminación de tus datos.
        </p>

        <h2>¿Cuánto tarda?</h2>
        <p>
            Resolvemos las solicitudes de eliminación en un plazo de hasta <strong><?php echo e($SLA); ?></strong>
            desde que verificamos tu identidad. Estos son los estados posibles de una solicitud:
        </p>
        <table class="pp-data-table">
            <thead>
                <tr><th>Estado</th><th>Qué significa</th></tr>
            </thead>
            <tbody>
                <tr><td>Pendiente</td><td>Recibimos tu pedido; falta verificar tu identidad.</td></tr>
                <tr><td>Verificada</td><td>Confirmaste tu identidad; tu pedido está en cola.</td></tr>
                <tr><td>En proceso</td><td>Estamos anonimizando tus datos.</td></tr>
                <tr><td>Completada</td><td>Tus datos personales fueron anonimizados de forma irreversible.</td></tr>
                <tr><td>Rechazada</td><td>No pudimos procesar el pedido (por ejemplo, no se verificó la identidad).</td></tr>
            </tbody>
        </table>

        <h2>Relación con WhatsApp / Meta</h2>
        <p>
            WhatsApp es un servicio de <strong>Meta Platforms, Inc.</strong> Como parte de las políticas de Meta
            para negocios que usan WhatsApp, Meta puede enviarnos automáticamente una <strong>solicitud de
            eliminación de datos</strong> en tu nombre (por ejemplo, si eliminás tu vinculación con nuestra app
            desde Facebook o Meta). Atiende recibe y procesa esas solicitudes a través de un canal seguro y firmado.
        </p>
        <p>
            En algunos casos, el identificador que Meta nos envía <strong>no nos permite reconocer tu número de
            teléfono</strong> automáticamente. Si eso ocurre, la solicitud queda <strong>pendiente de
            identificación</strong> y es posible que necesitemos un dato adicional para vincularla con tu
            información. En ese caso, contactanos según la <strong>Opción 1</strong> para completar la verificación.
        </p>
        <div class="pp-callout">
            Esta página es la que figura como <strong>&laquo;Data Deletion Instructions&raquo;</strong> en nuestra
            configuración del Meta Developer Portal.
        </div>

        <h2>Preguntas frecuentes</h2>
        <h3>¿Tengo que tener una cuenta para pedir la eliminación?</h3>
        <p>No. No hace falta iniciar sesión. Solo necesitás acceso al WhatsApp del número que querés eliminar.</p>

        <h3>¿Por qué me piden que verifique mi identidad?</h3>
        <p>Para confirmar que sos el titular del número. Así evitamos que otra persona pida eliminar tus datos.</p>

        <h3>Pedí la eliminación pero todavía recibo mensajes. ¿Por qué?</h3>
        <p>Hasta que la solicitud figure como <strong>Completada</strong>, tus datos siguen en el sistema.</p>

        <h3>¿Pueden quedar datos míos después de la eliminación?</h3>
        <p>
            No quedan datos que te identifiquen. Anonimizamos de forma irreversible todos tus datos personales.
            Solo se conservan registros transaccionales (por obligación fiscal y contable) <strong>sin ningún dato
            personal tuyo</strong>, disociados de tu identidad.
        </p>

        <h3>¿Quién es el responsable de mis datos?</h3>
        <p>
            <strong>Atiende</strong> (operada por <strong><?php echo e($RESPONSABLE); ?></strong>) es la responsable
            de la base de datos. Los comercios que usan la plataforma son encargados del tratamiento. Ante cualquier
            reclamo podés contactarnos a <a href="mailto:<?php echo e($EMAIL); ?>"><?php echo e($EMAIL); ?></a> o
            acudir a la <strong>Agencia de Acceso a la Información Pública (AAIP)</strong>.
        </p>

        <div class="pp-callout">
            ¿Querés conocer en detalle cómo tratamos tus datos? Leé nuestra
            <a href="politica-privacidad.php">Política de Privacidad</a>.
        </div>

    </div>
</div>

<footer class="pp-footer">
    <div class="container" style="max-width: 880px;">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>&copy; <?php echo date('Y'); ?> Atiende. Todos los derechos reservados.</span>
            <span>
                <a href="politica-privacidad.php">Política de Privacidad</a>
                &nbsp;&middot;&nbsp;
                <a href="index.php">Volver al inicio</a>
            </span>
        </div>
    </div>
</footer>

</body>
</html>
