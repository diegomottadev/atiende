# Security Audit — Atiende (PHP multi-tenant)

Sos un auditor de seguridad experto en PHP. Auditá la codebase en el 
directorio de trabajo actual buscando vulnerabilidades reales y 
explotables. El stack es: PHP + MySQLi (raw, sin ORM) + MariaDB + 
Docker + WhatsApp Cloud API. Es una app multi-tenant donde cada tenant 
tiene su propio schema `wb_{slug}`.

Buscá específicamente estos vectores, en orden de prioridad:

## 1. SQL INJECTION
- Búscá concatenaciones directas de input del usuario en queries SQL
  (ej: `"SELECT ... WHERE id = '".$_GET['id']."'"`)
- Revisá todos los archivos en ajax/, modelos/, pedidos/, ws/
- Prestá atención a ORDER BY, LIKE, IN() que no pueden usar bind params
- Para cada hallazgo: mostrá el archivo:línea, el snippet vulnerable,
  y la versión corregida con prepared statements

## 2. IDOR / AISLAMIENTO MULTI-TENANT
- ¿Puede un tenant leer datos de otro tenant?
- ¿Los endpoints en ajax/ verifican que el recurso pertenece al tenant 
  activo en $_SESSION['tenant_db']?
- ¿Hay queries que filtren solo por ID sin verificar tenantId?

## 3. XSS
- ¿Los datos de DB se imprimen con echo sin htmlspecialchars()?
- Revisá todas las vistas en vistas/, pedidos/, ws/m/
- ¿Los parámetros $_GET/$_POST se reflejan directamente en HTML?

## 4. CSRF
- ¿Los endpoints POST en ajax/ tienen token CSRF?
- ¿El login en vistas/login.php tiene protección CSRF?

## 5. SUBIDA DE ARCHIVOS
- Revisá ajax/up_file.php, ajax/subirarchivo.php, ajax/telefonosUpload.php
- ¿Se valida extensión Y magic bytes?
- ¿Los archivos subidos se guardan dentro del webroot ejecutable?
- ¿Se renombran con nombres aleatorios?

## 6. WEBHOOK WHATSAPP (ws/webhook.php)
- ¿Se verifica la firma HMAC-SHA256 del header X-Hub-Signature-256 
  antes de procesar el payload?
- ¿Se usa hash_equals() para comparar firmas (timing-safe)?

## 7. EXPOSICIÓN DE DATOS SENSIBLES
- ¿Hay stack traces o errores detallados visibles al usuario?
- ¿Algún endpoint devuelve más datos de los necesarios?
- ¿Hay credenciales hardcodeadas fuera de config/?

## 8. SESIONES
- ¿Se llama session_regenerate_id(true) después del login?
- ¿Las cookies de sesión tienen HttpOnly y Secure?
- ¿Hay verificación de autenticación en cada endpoint de ajax/ y 
  axadmin/?

Para cada vulnerabilidad encontrada entregá:
- Severidad: CRÍTICA / ALTA / MEDIA / BAJA
- Archivo y línea
- Descripción del problema
- Snippet vulnerable
- Fix concreto con código

Priorizá hallazgos CRÍTICOS y ALTOS. No reportes falsos positivos — 
solo vulnerabilidades reales y explotables.
