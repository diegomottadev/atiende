# pedidos-platform Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `pedidos-platform` — a standalone PHP SaaS control plane that automates tenant purchase, provisioning, and self-service management for Atiende/Atiende.

**Architecture:** Single PHP 8.1+ app with three access-controlled modules (landing/, superadmin/, portal/) plus public webhook handlers. All modules share a `saas_platform` MariaDB database. Tenant provisioning creates per-tenant `wb_{slug}` databases and writes config rows into the shared `axbot` database. Billing uses MercadoPago Preapproval (ARS) and Stripe Subscriptions (USD) with webhook-driven, idempotent provisioning.

**Tech Stack:** PHP 8.1+, MariaDB 10.6+, Composer (`phpmailer/phpmailer`, `stripe/stripe-php`, `vlucas/phpdotenv`), PHPUnit 10 (dev only)

**Spec:** `docs/superpowers/specs/2026-05-26-pedidos-platform-design.md`  
**Working directory:** Create `pedidos-platform/` inside `C:\Users\ACER\Downloads\whatsbus2021-main\` (sibling of `whatsbus2021-main\`). Full path: `C:\Users\ACER\Downloads\whatsbus2021-main\pedidos-platform\`.

---

## File Map

```
pedidos-platform/
├── composer.json
├── composer.lock
├── .env.example
├── .env                         ← git-ignored
├── .gitignore
├── index.php                    ← redirect to /landing/
├── login.php                    ← unified login for all roles
├── logout.php
├── config/
│   ├── bootstrap.php            ← require vendor/autoload, load .env, set constants
│   └── database.php             ← returns PDO for saas_platform; axbot; provisioner user
├── landing/
│   ├── index.php                ← show active plans + registration form
│   ├── checkout.php            ← POST: create checkout_session, redirect to MP/Stripe
│   └── success.php             ← redirect destination after successful payment
├── superadmin/
│   ├── index.php                ← auth guard (superadmin role), redirect to dashboard
│   ├── dashboard.php            ← tenant list + pending_whatsapp badge
│   ├── tenant.php               ← GET: tenant detail + WhatsApp credentials form
│   ├── tenant_save.php          ← POST: save WhatsApp credentials
│   ├── activate.php             ← POST: activate tenant
│   ├── suspend.php              ← POST: suspend tenant
│   ├── reactivate.php           ← POST: reactivate tenant
│   ├── teardown.php             ← POST: teardown with slug confirmation
│   └── planes.php               ← GET/POST: CRUD plans
├── portal/
│   ├── index.php                ← auth guard (tenant_admin + activo), redirect to bot
│   ├── bot.php                  ← GET/POST: BotEngine JSON editor
│   ├── usuarios.php             ← GET/POST: manage usuarios in tenant DB
│   └── suscripcion.php         ← GET/POST: subscription view + cancel
├── webhooks/
│   ├── mp.php                   ← MercadoPago Preapproval webhook endpoint
│   └── stripe.php               ← Stripe webhook endpoint
├── modelos/
│   ├── Auth.php                 ← login/logout, session guards, CSRF tokens
│   ├── Encryption.php           ← AES-256-GCM encrypt/decrypt for WA tokens
│   ├── ProvisioningService.php  ← provision/suspend/reactivate/teardown
│   ├── BillingService.php       ← cancel subscription at MP/Stripe, grace period
│   ├── MailService.php          ← PHPMailer wrapper, send named templates
│   └── BillingService.php       ← cancel subscription at MP/Stripe
├── views/
│   ├── layout/
│   │   ├── header.php           ← HTML head, Bootstrap 5 CDN, nav
│   │   └── footer.php           ← closing tags
│   └── emails/
│       ├── bienvenida.php       ← welcome email with portal credentials
│       ├── activacion.php       ← "your account is active" email
│       ├── pendiente_whatsapp.php ← notify Diego of new tenant needing WA
│       ├── grace_period.php     ← payment problem warning to tenant
│       └── cancelacion.php     ← subscription cancelled confirmation
├── templates/
│   ├── sql/
│   │   ├── tenant_schema.sql    ← DDL-only extract of atiende.sql (no data)
│   │   └── axbot_row_insert.sql ← parameterized INSERT for axbot.empresa
│   └── bot/
│       └── default_menu.json    ← generic bot menu JSON for BotEngine
├── db/
│   └── saas_platform.sql        ← full CREATE TABLE statements for saas_platform
├── cli/
│   └── suspend_expired.php      ← cron: suspend tenants past grace_period_fin
└── tests/
    ├── bootstrap.php            ← PHPUnit bootstrap: load config/bootstrap.php
    ├── EncryptionTest.php
    ├── ProvisioningServiceTest.php
    └── WebhookIdempotencyTest.php
```

---

## Task 1: Project Bootstrap

**Files:**
- Create: `pedidos-platform/composer.json`
- Create: `pedidos-platform/.env.example`
- Create: `pedidos-platform/.gitignore`
- Create: `pedidos-platform/config/bootstrap.php`
- Create: `pedidos-platform/config/database.php`

- [ ] **Step 1.1: Create project directory and composer.json**

```bash
mkdir pedidos-platform && cd pedidos-platform
```

`composer.json`:
```json
{
    "name": "pedidos/platform",
    "require": {
        "php": ">=8.1",
        "phpmailer/phpmailer": "^6.9",
        "stripe/stripe-php": "^13.0",
        "vlucas/phpdotenv": "^5.6"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "modelos/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 1.2: Create .env.example**

```dotenv
APP_URL=http://localhost:8080
APP_ENV=development

# MariaDB
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=saas_platform
DB_USER=pp_app
DB_PASS=changeme
DB_PROVISIONER_USER=pp_provisioner
DB_PROVISIONER_PASS=changeme
DB_AXBOT_NAME=axbot

# Encryption
PLATFORM_ENCRYPTION_KEY=change_this_to_32_bytes_hex_string

# SMTP
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=587
SMTP_USER=your_smtp_user
SMTP_PASS=your_smtp_pass
SMTP_FROM=noreply@pedidos.app
SMTP_FROM_NAME=Pedidos Platform
SUPERADMIN_EMAIL=diego@example.com

# MercadoPago
MP_ACCESS_TOKEN=your_mp_access_token
MP_WEBHOOK_SECRET=your_mp_webhook_secret

# Stripe
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

- [ ] **Step 1.3: Create .gitignore**

```gitignore
/vendor/
/.env
```

- [ ] **Step 1.4: Create config/bootstrap.php**

```php
<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT);
$dotenv->load();

define('APP_URL', rtrim($_ENV['APP_URL'], '/'));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (APP_ENV !== 'development') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.gc_maxlifetime', 7200);
```

- [ ] **Step 1.5: Create config/database.php**

```php
<?php
require_once __DIR__ . '/bootstrap.php';

function getPlatformPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']);
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function getAxbotPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_AXBOT_NAME']);
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function getProvisionerPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4',
            $_ENV['DB_HOST'], $_ENV['DB_PORT']);
        $pdo = new PDO($dsn, $_ENV['DB_PROVISIONER_USER'], $_ENV['DB_PROVISIONER_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    return $pdo;
}
```

- [ ] **Step 1.6: Install dependencies**

```bash
composer install
```

Expected: `vendor/` created, no errors.

- [ ] **Step 1.7: Commit**

```bash
git init
git add composer.json .env.example .gitignore config/
git commit -m "feat: bootstrap pedidos-platform project"
```

---

## Task 2: saas_platform Database Schema

**Files:**
- Create: `pedidos-platform/db/saas_platform.sql`

- [ ] **Step 2.1: Write saas_platform.sql**

```sql
CREATE DATABASE IF NOT EXISTS `saas_platform`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `saas_platform`;

CREATE TABLE `plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `precio_ars` DECIMAL(10,2) NOT NULL,
  `precio_usd` DECIMAL(10,2) NOT NULL,
  `stripe_price_id` VARCHAR(100) NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Set stripe_price_id after creating Stripe products in the dashboard
INSERT INTO `plans` (`nombre`, `precio_ars`, `precio_usd`, `stripe_price_id`) VALUES
  ('Plan Mensual', 15000.00, 15.00, NULL),
  ('Plan Anual', 150000.00, 150.00, NULL);

CREATE TABLE `tenants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(250) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `estado` ENUM('pendiente_pago','pendiente_whatsapp','activo','suspendido','cancelado') NOT NULL DEFAULT 'pendiente_pago',
  `db_name` VARCHAR(60) NOT NULL DEFAULT '',
  `plan_id` INT UNSIGNED NOT NULL,
  `whatsapp_phone_id` VARCHAR(100) NULL DEFAULT NULL,
  `whatsapp_waba_id` VARCHAR(100) NULL DEFAULT NULL,
  `whatsapp_token_enc` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`),
  CONSTRAINT `fk_tenants_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `checkout_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token` VARCHAR(64) NOT NULL,
  `nombre` VARCHAR(250) NOT NULL,
  `empresa` VARCHAR(250) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `provider` ENUM('mercadopago','stripe') NOT NULL,
  `provider_ref` VARCHAR(255) NULL DEFAULT NULL,
  `estado` ENUM('pendiente','procesando','completado','fallido') NOT NULL DEFAULT 'pendiente',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token_unique` (`session_token`),
  CONSTRAINT `fk_checkout_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `provider` ENUM('mercadopago','stripe') NOT NULL,
  `provider_subscription_id` VARCHAR(255) NOT NULL,
  `estado` ENUM('activa','vencida','cancelada') NOT NULL DEFAULT 'activa',
  `periodo_fin` DATETIME NOT NULL,
  `grace_period_fin` DATETIME NULL DEFAULT NULL,
  `payment_failure_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_sub_unique` (`provider_subscription_id`),
  CONSTRAINT `fk_sub_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `platform_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NULL DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('superadmin','tenant_admin') NOT NULL DEFAULT 'tenant_admin',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  CONSTRAINT `fk_user_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `webhook_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` ENUM('mercadopago','stripe') NOT NULL,
  `provider_event_id` VARCHAR(255) NOT NULL,
  `evento` VARCHAR(100) NOT NULL,
  `procesado` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_unique` (`provider`, `provider_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Superadmin user (change password after first login)
-- Password: admin1234 (bcrypt)
INSERT INTO `platform_users` (`tenant_id`, `email`, `password_hash`, `rol`)
VALUES (NULL, 'admin@pedidos.app', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');
```

- [ ] **Step 2.2: Create the DB on your MariaDB instance**

```bash
mysql -u root -p < db/saas_platform.sql
```

Expected: No errors. Tables created in `saas_platform`.

- [ ] **Step 2.3: Create MariaDB users with minimal privileges**

```sql
-- Run as root on MariaDB
CREATE USER 'pp_app'@'localhost' IDENTIFIED BY 'changeme';
GRANT SELECT, INSERT, UPDATE, DELETE ON saas_platform.* TO 'pp_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON axbot.empresa TO 'pp_app'@'localhost';
FLUSH PRIVILEGES;

CREATE USER 'pp_provisioner'@'localhost' IDENTIFIED BY 'changeme';
GRANT CREATE, DROP ON `wb_%`.* TO 'pp_provisioner'@'localhost';
GRANT SELECT ON saas_platform.* TO 'pp_provisioner'@'localhost';
FLUSH PRIVILEGES;
```

- [ ] **Step 2.4: Update .env with real credentials and run smoke test**

```bash
cp .env.example .env
# Edit .env: set DB_HOST, DB_USER=pp_app, DB_PASS, DB_PROVISIONER_USER=pp_provisioner
php -r "require 'config/database.php'; getPlatformPDO(); echo 'DB OK\n';"
```

Expected: `DB OK`

- [ ] **Step 2.5: Commit**

```bash
git add db/saas_platform.sql
git commit -m "feat: add saas_platform schema and DB users"
```

---

## Task 3: Tenant Templates

**Files:**
- Create: `pedidos-platform/templates/sql/tenant_schema.sql`
- Create: `pedidos-platform/templates/sql/axbot_row_insert.sql`
- Create: `pedidos-platform/templates/bot/default_menu.json`

These templates are extracted from the existing Atiende project.

- [ ] **Step 3.1: Extract DDL-only tenant_schema.sql from atiende.sql**

Open `../whatsbus2021-main/_docker/mariadb/atiende.sql`. Copy every `CREATE TABLE` block. Remove all `INSERT INTO` and seed data. Remove `DROP TABLE IF EXISTS` lines. Save as `templates/sql/tenant_schema.sql`.

The file should start with:
```sql
SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;
-- Tables: areas, areas_consultas, articulos, cart_cart_item, cart_items,
-- carts, clientes, consultas, contactos, contactosb2c, emprendedores,
-- fidelizar, link_pedidos, mensajeb2c_contactob2c, mensajes, mensajesb2c,
-- menuitem, motivo_reclamos, motivo_consultas, msj_consultas, msj_reclamos,
-- pedidos, permiso, reclamos, repartidores, solicitudes, telefonos,
-- usuario, usuario_permiso, vendedores
```

- [ ] **Step 3.2: Create axbot_row_insert.sql**

```sql
-- Parameterized INSERT for axbot.empresa
-- Used by ProvisioningService: replace :slug, :clave, :json_content at runtime
INSERT INTO `empresa` (`empresa`, `clave`, `telefono`, `json`, `online`, `activo`)
VALUES (:slug, :clave, '', :json_content, 0, 1);
```

- [ ] **Step 3.3: Create default_menu.json**

Create a minimal valid BotEngine menu JSON. Every entry must have `menuId`, `consigna`, `finaliza`, `menuItem`. This is the initial menu before the tenant customizes it via the portal:

```json
[
  {
    "menuId": "0",
    "menuIdB": "100",
    "consigna": "",
    "finaliza": "false",
    "menuItem": []
  },
  {
    "menuId": "100",
    "consigna": "<saludo> <nombre>, bienvenido a nuestro servicio. Por favor ingresa tu código de cliente:",
    "finaliza": "false",
    "menuItem": [
      {
        "opcionId": "",
        "opcion": "",
        "menuId": "200",
        "guardar": "false",
        "area": "",
        "accion": "registraNumero"
      }
    ]
  },
  {
    "menuId": "200",
    "consigna": "<saludo> <nombre>! ¿En qué podemos ayudarte?",
    "finaliza": "false",
    "menuItem": [
      {
        "opcionId": "1",
        "opcion": "Hacer un pedido",
        "menuId": "300",
        "guardar": "false",
        "area": ""
      },
      {
        "opcionId": "2",
        "opcion": "Consultar reclamo",
        "menuId": "400",
        "guardar": "false",
        "area": ""
      }
    ]
  },
  {
    "menuId": "300",
    "consigna": "Te enviaremos el link para tu pedido.",
    "finaliza": "true",
    "palabraClave": ["pedido", "comprar"],
    "menuItem": [
      {
        "opcionId": "",
        "opcion": "",
        "menuId": "2.2",
        "guardar": "false",
        "area": "",
        "accion": "confirmaPedido"
      }
    ]
  },
  {
    "menuId": "400",
    "consigna": "Por favor ingresa tu número de reclamo:",
    "finaliza": "false",
    "menuItem": [
      {
        "opcionId": "",
        "opcion": "",
        "menuId": "2.2",
        "guardar": "false",
        "area": "",
        "accion": "consultarReclamo"
      }
    ]
  },
  {
    "menuId": "2.2",
    "consigna": "Gracias. Hasta pronto!",
    "finaliza": "true",
    "menuItem": []
  },
  {
    "menuId": "4",
    "consigna": "Opción no válida. Por favor seleccioná una de las opciones del menú.",
    "finaliza": "false",
    "menuItem": []
  }
]
```

- [ ] **Step 3.4: Commit**

```bash
git add templates/
git commit -m "feat: add tenant SQL schema template and default bot menu"
```

---

## Task 4: Encryption Helper

**Files:**
- Create: `pedidos-platform/modelos/Encryption.php`
- Create: `pedidos-platform/tests/EncryptionTest.php`
- Create: `pedidos-platform/tests/bootstrap.php`
- Create: `pedidos-platform/phpunit.xml`

- [ ] **Step 4.1: Write the failing test**

`tests/bootstrap.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
```

`phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="pedidos-platform">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

`tests/EncryptionTest.php`:
```php
<?php
use PHPUnit\Framework\TestCase;
use App\Encryption;

class EncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['PLATFORM_ENCRYPTION_KEY'] = bin2hex(random_bytes(32));
    }

    public function test_encrypt_then_decrypt_returns_original(): void
    {
        $original = 'EAABsbCS1iHgBOZD...my_wa_token';
        $encrypted = Encryption::encrypt($original);
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, Encryption::decrypt($encrypted));
    }

    public function test_two_encryptions_produce_different_ciphertext(): void
    {
        $token = 'same_token';
        $this->assertNotEquals(Encryption::encrypt($token), Encryption::encrypt($token));
    }

    public function test_decrypt_with_wrong_key_throws(): void
    {
        $encrypted = Encryption::encrypt('secret');
        $_ENV['PLATFORM_ENCRYPTION_KEY'] = bin2hex(random_bytes(32)); // different key
        $this->expectException(\RuntimeException::class);
        Encryption::decrypt($encrypted);
    }

    public function test_mask_shows_last_eight_chars(): void
    {
        $token = 'ABCDEF1234567890';
        $this->assertEquals('...34567890', Encryption::mask($token));
    }
}
```

- [ ] **Step 4.2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/EncryptionTest.php
```

Expected: FAIL — `Class App\Encryption not found`

- [ ] **Step 4.3: Implement Encryption.php**

`modelos/Encryption.php`:
```php
<?php
namespace App;

class Encryption
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    public static function encrypt(string $plaintext): string
    {
        $key = self::getKey();
        $iv  = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $encoded): string
    {
        $key  = self::getKey();
        $raw  = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH + 1) {
            throw new \RuntimeException('Invalid ciphertext');
        }
        $iv         = substr($raw, 0, self::IV_LENGTH);
        $tag        = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);
        $plaintext  = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed — wrong key or tampered data');
        }
        return $plaintext;
    }

    public static function mask(string $token): string
    {
        return '...' . substr($token, -8);
    }

    private static function getKey(): string
    {
        $hex = $_ENV['PLATFORM_ENCRYPTION_KEY'] ?? '';
        if (strlen($hex) !== 64) {
            throw new \RuntimeException('PLATFORM_ENCRYPTION_KEY must be 64 hex chars (32 bytes)');
        }
        return hex2bin($hex);
    }
}
```

- [ ] **Step 4.4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/EncryptionTest.php
```

Expected: 4 tests, 4 assertions, PASS.

- [ ] **Step 4.5: Commit**

```bash
git add modelos/Encryption.php tests/ phpunit.xml
git commit -m "feat: add AES-256-GCM encryption helper with tests"
```

---

## Task 5: Auth System

**Files:**
- Create: `pedidos-platform/modelos/Auth.php`
- Create: `pedidos-platform/login.php`
- Create: `pedidos-platform/logout.php`
- Create: `pedidos-platform/index.php`

- [ ] **Step 5.1: Implement Auth.php**

`modelos/Auth.php`:
```php
<?php
namespace App;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Session timeout: 2 hours
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
            self::logout();
        }
        $_SESSION['last_activity'] = time();
    }

    public static function attempt(string $email, string $password): bool
    {
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare(
            'SELECT id, password_hash, rol, tenant_id, activo FROM platform_users WHERE email = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !$user['activo'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['rol']       = $user['rol'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        // Update last_login
        $pdo->prepare('UPDATE platform_users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        session_destroy();
    }

    public static function requireSuperadmin(): void
    {
        self::start();
        if (($_SESSION['rol'] ?? '') !== 'superadmin') {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    public static function requireTenantAdmin(): void
    {
        self::start();
        if (($_SESSION['rol'] ?? '') !== 'tenant_admin') {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
        // Verify tenant is active
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare('SELECT estado FROM tenants WHERE id = ?');
        $stmt->execute([$_SESSION['tenant_id']]);
        $tenant = $stmt->fetch();
        if (!$tenant || $tenant['estado'] !== 'activo') {
            self::logout();
            header('Location: ' . APP_URL . '/login.php?msg=suspended');
            exit;
        }
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void
    {
        self::start();
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }
    }
}
```

- [ ] **Step 5.2: Create login.php**

`login.php`:
```php
<?php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
use App\Auth;

Auth::start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    if (Auth::attempt($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        $rol = $_SESSION['rol'];
        header('Location: ' . APP_URL . ($rol === 'superadmin' ? '/superadmin/' : '/portal/'));
        exit;
    }
    $error = 'Email o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Login — Pedidos Platform</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container" style="max-width:400px;margin-top:100px">
  <h4 class="mb-4 text-center">Pedidos Platform</h4>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <div class="mb-3"><label>Email</label>
      <input type="email" name="email" class="form-control" required autofocus></div>
    <div class="mb-3"><label>Contraseña</label>
      <input type="password" name="password" class="form-control" required></div>
    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
  </form>
</div>
</body></html>
```

- [ ] **Step 5.3: Create logout.php and index.php**

`logout.php`:
```php
<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth;
Auth::start();
Auth::logout();
header('Location: ' . APP_URL . '/login.php');
exit;
```

`index.php`:
```php
<?php
require_once __DIR__ . '/config/bootstrap.php';
header('Location: ' . APP_URL . '/landing/');
exit;
```

- [ ] **Step 5.4: Manual smoke test**

Start PHP dev server: `php -S localhost:8080`  
Open `http://localhost:8080/login.php`  
Try wrong credentials → error message shown.  
Try correct credentials (admin@pedidos.app / admin1234) → redirected to `/superadmin/` (which may 404 yet — that's OK).

- [ ] **Step 5.5: Commit**

```bash
git add modelos/Auth.php login.php logout.php index.php
git commit -m "feat: add auth system with CSRF, session timeout, role guards"
```

---

## Task 6: Shared Layout Views

**Files:**
- Create: `pedidos-platform/views/layout/header.php`
- Create: `pedidos-platform/views/layout/footer.php`

- [ ] **Step 6.1: Create header.php**

```php
<?php
// $pageTitle and $activeModule should be set by the including page
$title = $pageTitle ?? 'Pedidos Platform';
$module = $activeModule ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> — Pedidos Platform</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark px-3 mb-4">
  <span class="navbar-brand">Pedidos Platform</span>
  <?php if ($module === 'superadmin'): ?>
  <div class="d-flex gap-3">
    <a href="<?= APP_URL ?>/superadmin/dashboard.php" class="text-white text-decoration-none">Tenants</a>
    <a href="<?= APP_URL ?>/superadmin/planes.php" class="text-white text-decoration-none">Planes</a>
    <a href="<?= APP_URL ?>/logout.php" class="text-white text-decoration-none">Salir</a>
  </div>
  <?php elseif ($module === 'portal'): ?>
  <div class="d-flex gap-3">
    <a href="<?= APP_URL ?>/portal/bot.php" class="text-white text-decoration-none">Bot</a>
    <a href="<?= APP_URL ?>/portal/usuarios.php" class="text-white text-decoration-none">Usuarios</a>
    <a href="<?= APP_URL ?>/portal/suscripcion.php" class="text-white text-decoration-none">Suscripción</a>
    <a href="<?= APP_URL ?>/logout.php" class="text-white text-decoration-none">Salir</a>
  </div>
  <?php endif ?>
</nav>
<div class="container">
```

- [ ] **Step 6.2: Create footer.php**

```php
</div><!-- /container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 6.3: Commit**

```bash
git add views/
git commit -m "feat: add shared layout views"
```

---

## Task 7: MailService

**Files:**
- Create: `pedidos-platform/modelos/MailService.php`
- Create: `pedidos-platform/views/emails/bienvenida.php`
- Create: `pedidos-platform/views/emails/activacion.php`
- Create: `pedidos-platform/views/emails/pendiente_whatsapp.php`
- Create: `pedidos-platform/views/emails/grace_period.php`
- Create: `pedidos-platform/views/emails/cancelacion.php`

- [ ] **Step 7.1: Implement MailService.php**

`modelos/MailService.php`:
```php
<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class MailService
{
    public static function send(string $to, string $subject, string $template, array $vars = []): void
    {
        $body = self::render($template, $vars);
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$_ENV['SMTP_PORT'];
        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
    }

    private static function render(string $template, array $vars): string
    {
        $templatePath = ROOT . '/views/emails/' . $template . '.php';
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Email template not found: $template");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
```

- [ ] **Step 7.2: Create email templates**

`views/emails/bienvenida.php`:
```html
<h2>¡Bienvenido a Pedidos Platform!</h2>
<p>Tu cuenta para <strong><?= htmlspecialchars($empresa) ?></strong> ha sido creada.</p>
<p>Accedé al portal con estas credenciales:</p>
<ul>
  <li><strong>URL:</strong> <a href="<?= APP_URL ?>/portal/"><?= APP_URL ?>/portal/</a></li>
  <li><strong>Email:</strong> <?= htmlspecialchars($email) ?></li>
  <li><strong>Contraseña temporal:</strong> <?= htmlspecialchars($password) ?></li>
</ul>
<p>En breve activaremos tu número de WhatsApp y recibirás otro email de confirmación.</p>
```

`views/emails/activacion.php`:
```html
<h2>¡Tu cuenta está activa!</h2>
<p>El número de WhatsApp de <strong><?= htmlspecialchars($empresa) ?></strong> ya fue configurado.</p>
<p>Ya podés empezar a usar el bot y gestionar tu cuenta desde el portal:</p>
<p><a href="<?= APP_URL ?>/portal/"><?= APP_URL ?>/portal/</a></p>
```

`views/emails/pendiente_whatsapp.php`:
```html
<h2>Nuevo tenant pendiente de activación</h2>
<p>La empresa <strong><?= htmlspecialchars($empresa) ?></strong> completó el pago.</p>
<p>Tenés que cargar las credenciales de WhatsApp para activarla:</p>
<p><a href="<?= APP_URL ?>/superadmin/tenant.php?id=<?= $tenant_id ?>">Ver en Superadmin</a></p>
```

`views/emails/grace_period.php`:
```html
<h2>Problema con el pago de tu suscripción</h2>
<p>Hola <?= htmlspecialchars($empresa) ?>,</p>
<p>No pudimos procesar el pago de tu suscripción. Tu cuenta permanecerá activa hasta el <strong><?= htmlspecialchars($grace_until) ?></strong>.</p>
<p>Por favor actualizá tu método de pago para evitar la suspensión.</p>
```

`views/emails/cancelacion.php`:
```html
<h2>Suscripción cancelada</h2>
<p>Tu suscripción para <strong><?= htmlspecialchars($empresa) ?></strong> fue cancelada.</p>
<p>Tu cuenta permanecerá activa hasta el <strong><?= htmlspecialchars($periodo_fin) ?></strong>.</p>
```

- [ ] **Step 7.3: Verify MailService sends (manual)**

Set up Mailtrap or a test SMTP in .env, then run:
```bash
php -r "
require 'config/bootstrap.php';
require 'config/database.php';
use App\MailService;
MailService::send('test@example.com', 'Test', 'bienvenida', [
  'empresa' => 'TestCorp',
  'email' => 'user@test.com',
  'password' => 'temp1234'
]);
echo 'Mail sent\n';
"
```

Expected: `Mail sent` — email visible in Mailtrap.

- [ ] **Step 7.4: Commit**

```bash
git add modelos/MailService.php views/emails/
git commit -m "feat: add MailService with PHPMailer and email templates"
```

---

## Task 8: ProvisioningService

**Files:**
- Create: `pedidos-platform/modelos/ProvisioningService.php`
- Create: `pedidos-platform/tests/ProvisioningServiceTest.php`

The most critical class in the system. Uses compensating actions — not cross-DB transactions.

- [ ] **Step 8.1: Write the failing tests**

`tests/ProvisioningServiceTest.php`:
```php
<?php
use PHPUnit\Framework\TestCase;
use App\ProvisioningService;

class ProvisioningServiceTest extends TestCase
{
    public function test_generateSlug_from_company_name(): void
    {
        $svc = new ProvisioningService();
        // Access private via reflection
        $ref = new ReflectionMethod(ProvisioningService::class, 'generateSlug');
        $ref->setAccessible(true);

        $this->assertEquals('mi_empresa', $ref->invoke($svc, 'Mi Empresa'));
        $this->assertEquals('acme_sa', $ref->invoke($svc, 'ACME S.A.'));
        $this->assertEquals('cafe_bar', $ref->invoke($svc, 'Café & Bar'));
        $this->assertEquals('empresa123', $ref->invoke($svc, 'Empresa 123'));
    }

    public function test_generateSlug_strips_leading_numbers(): void
    {
        $svc = new ProvisioningService();
        $ref = new ReflectionMethod(ProvisioningService::class, 'generateSlug');
        $ref->setAccessible(true);
        $slug = $ref->invoke($svc, '123 Corp');
        $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]{1,49}$/', $slug);
    }
}
```

- [ ] **Step 8.2: Run to verify fail**

```bash
./vendor/bin/phpunit tests/ProvisioningServiceTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 8.3: Implement ProvisioningService.php**

`modelos/ProvisioningService.php`:
```php
<?php
namespace App;

class ProvisioningService
{
    public function provision(int $checkoutSessionId): void
    {
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare('SELECT * FROM checkout_sessions WHERE id = ? AND estado = ?');
        $stmt->execute([$checkoutSessionId, 'pendiente']);
        $session = $stmt->fetch();
        if (!$session) {
            throw new \RuntimeException("checkout_session $checkoutSessionId not found or not pending");
        }

        $slug      = $this->ensureUniqueSlug($this->generateSlug($session['empresa']));
        $dbName    = 'wb_' . $slug;
        $clave     = bin2hex(random_bytes(16));
        $tempPass  = bin2hex(random_bytes(8));

        $paso = 0;
        try {
            // Step 1: create tenant DB
            $paso = 1;
            $prov = getProvisionerPDO();
            $prov->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_spanish_ci");

            // Step 2: import tenant schema
            $paso = 2;
            $sql = file_get_contents(ROOT . '/templates/sql/tenant_schema.sql');
            $prov->exec("USE `$dbName`");
            foreach (explode(';', $sql) as $statement) {
                $s = trim($statement);
                if ($s !== '') {
                    $prov->exec($s);
                }
            }

            // Step 3: insert axbot.empresa row
            $paso = 3;
            $menuJson = file_get_contents(ROOT . '/templates/bot/default_menu.json');
            $axbot = getAxbotPDO();
            $axbot->prepare(
                'INSERT IGNORE INTO empresa (empresa, clave, telefono, json, online, activo) VALUES (?,?,?,?,0,1)'
            )->execute([$slug, $clave, '', $menuJson]);

            // Step 4: create platform_user
            $paso = 4;
            $hash = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                'INSERT INTO platform_users (tenant_id, email, password_hash, rol) VALUES (?,?,?,?)'
            )->execute([null, $session['email'], $hash, 'tenant_admin']); // tenant_id set after tenant insert
            $userId = (int)$pdo->lastInsertId();

            // Step 5: insert tenant + subscription
            $paso = 5;
            $pdo->prepare(
                'INSERT INTO tenants (slug, nombre, email, estado, db_name, plan_id) VALUES (?,?,?,?,?,?)'
            )->execute([$slug, $session['empresa'], $session['email'], 'pendiente_whatsapp', $dbName, $session['plan_id']]);
            $tenantId = (int)$pdo->lastInsertId();

            // Link user to tenant
            $pdo->prepare('UPDATE platform_users SET tenant_id = ? WHERE id = ?')->execute([$tenantId, $userId]);

            // Step 6: insert subscription record (provider_subscription_id from session)
            $paso = 6;
            $pdo->prepare(
                'INSERT INTO subscriptions (tenant_id, provider, provider_subscription_id, estado, periodo_fin) VALUES (?,?,?,?,DATE_ADD(NOW(), INTERVAL 1 MONTH))'
            )->execute([$tenantId, $session['provider'], $session['provider_ref'] ?? 'pending_' . $session['session_token'], 'activa']);

            // Step 7: send emails
            $paso = 7;
            MailService::send($session['email'], 'Bienvenido a Pedidos Platform', 'bienvenida', [
                'empresa'  => $session['empresa'],
                'email'    => $session['email'],
                'password' => $tempPass,
            ]);
            MailService::send($_ENV['SUPERADMIN_EMAIL'], 'Nuevo tenant: ' . $session['empresa'], 'pendiente_whatsapp', [
                'empresa'   => $session['empresa'],
                'tenant_id' => $tenantId,
            ]);

            // Step 8: mark checkout_session completed
            $pdo->prepare('UPDATE checkout_sessions SET estado = ? WHERE id = ?')->execute(['completado', $checkoutSessionId]);

        } catch (\Throwable $e) {
            error_log('[ProvisioningService] FAIL paso=' . $paso . ' ' . $e->getMessage());
            $this->compensate($paso, $dbName, $slug, $userId ?? null, $tenantId ?? null, $checkoutSessionId, $pdo);
            throw $e;
        }
    }

    private function compensate(int $paso, string $dbName, string $slug, ?int $userId, ?int $tenantId, int $sessionId, \PDO $pdo): void
    {
        try {
            if ($paso >= 6 && $tenantId) {
                $pdo->prepare('DELETE FROM subscriptions WHERE tenant_id = ?')->execute([$tenantId]);
            }
            if ($paso >= 5 && $tenantId) {
                $pdo->prepare('DELETE FROM tenants WHERE id = ?')->execute([$tenantId]);
            }
            if ($paso >= 4 && $userId) {
                $pdo->prepare('DELETE FROM platform_users WHERE id = ?')->execute([$userId]);
            }
            if ($paso >= 3) {
                getAxbotPDO()->prepare('DELETE FROM empresa WHERE empresa = ?')->execute([$slug]);
            }
            if ($paso >= 1) {
                getProvisionerPDO()->exec("DROP DATABASE IF EXISTS `$dbName`");
            }
            $pdo->prepare('UPDATE checkout_sessions SET estado = ? WHERE id = ?')->execute(['fallido', $sessionId]);
        } catch (\Throwable $ce) {
            error_log('[ProvisioningService] compensate FAIL: ' . $ce->getMessage());
        }
    }

    public function suspend(int $tenantId): void
    {
        $pdo = getPlatformPDO();
        $pdo->prepare("UPDATE tenants SET estado = 'suspendido' WHERE id = ?")->execute([$tenantId]);
        $tenant = $pdo->prepare('SELECT slug FROM tenants WHERE id = ?');
        $tenant->execute([$tenantId]);
        $row = $tenant->fetch();
        if ($row) {
            getAxbotPDO()->prepare("UPDATE empresa SET activo = 0 WHERE empresa = ?")->execute([$row['slug']]);
        }
    }

    public function reactivate(int $tenantId): void
    {
        $pdo = getPlatformPDO();
        $pdo->prepare("UPDATE tenants SET estado = 'activo' WHERE id = ?")->execute([$tenantId]);
        $stmt = $pdo->prepare('SELECT slug FROM tenants WHERE id = ?');
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        if ($row) {
            getAxbotPDO()->prepare("UPDATE empresa SET activo = 1 WHERE empresa = ?")->execute([$row['slug']]);
        }
    }

    public function teardown(int $tenantId): void
    {
        $pdo  = getPlatformPDO();
        $stmt = $pdo->prepare(
            "SELECT t.slug, t.db_name, s.estado AS sub_estado FROM tenants t
             LEFT JOIN subscriptions s ON s.tenant_id = t.id
             WHERE t.id = ? AND t.deleted_at IS NULL"
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException("Tenant $tenantId not found");
        }
        if ($row['sub_estado'] !== 'cancelada') {
            throw new \RuntimeException("Cannot teardown: subscription must be cancelled first");
        }
        // Drop tenant DB
        getProvisionerPDO()->exec("DROP DATABASE IF EXISTS `{$row['db_name']}`");
        // Remove axbot row
        getAxbotPDO()->prepare('DELETE FROM empresa WHERE empresa = ?')->execute([$row['slug']]);
        // Soft-delete platform records
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE tenants SET estado='cancelado', deleted_at=? WHERE id=?")->execute([$now, $tenantId]);
        $pdo->prepare("UPDATE platform_users SET deleted_at=? WHERE tenant_id=?")->execute([$now, $tenantId]);
        $pdo->prepare("UPDATE subscriptions SET deleted_at=? WHERE tenant_id=?")->execute([$now, $tenantId]);
    }

    public function updatePeriodFin(int $tenantId, \DateTime $newPeriodFin): void
    {
        getPlatformPDO()->prepare(
            "UPDATE subscriptions SET periodo_fin=?, payment_failure_count=0, grace_period_fin=NULL WHERE tenant_id=? AND estado='activa'"
        )->execute([$newPeriodFin->format('Y-m-d H:i:s'), $tenantId]);
    }

    private function generateSlug(string $companyName): string
    {
        $slug = mb_strtolower($companyName, 'UTF-8');
        // Transliterate accents
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        // Replace non-alphanumeric with underscore
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        // Must start with letter
        if (strlen($slug) === 0 || !ctype_alpha($slug[0])) {
            $slug = 'empresa_' . $slug;
        }
        return substr($slug, 0, 50);
    }

    private function ensureUniqueSlug(string $base): string
    {
        $pdo  = getPlatformPDO();
        $slug = $base;
        $i    = 1;
        while (true) {
            $stmt = $pdo->prepare('SELECT id FROM tenants WHERE slug = ?');
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $base . '_' . $i++;
        }
    }
}
```

- [ ] **Step 8.4: Run tests**

```bash
./vendor/bin/phpunit tests/ProvisioningServiceTest.php
```

Expected: PASS.

- [ ] **Step 8.5: Commit**

```bash
git add modelos/ProvisioningService.php tests/ProvisioningServiceTest.php
git commit -m "feat: add ProvisioningService with compensating actions"
```

---

## Task 9: Webhook Idempotency Test + Handlers

**Files:**
- Create: `pedidos-platform/tests/WebhookIdempotencyTest.php`
- Create: `pedidos-platform/webhooks/mp.php`
- Create: `pedidos-platform/webhooks/stripe.php`

- [ ] **Step 9.1: Write idempotency test**

`tests/WebhookIdempotencyTest.php`:
```php
<?php
use PHPUnit\Framework\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = getPlatformPDO();
        $this->pdo->exec("DELETE FROM webhook_events WHERE provider_event_id LIKE 'test_%'");
    }

    public function test_first_insert_returns_one_affected_row(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)'
        );
        $stmt->execute(['stripe', 'test_evt_001', 'invoice.payment_succeeded']);
        $this->assertEquals(1, $stmt->rowCount());
    }

    public function test_duplicate_insert_returns_zero_affected_rows(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)'
        );
        $stmt->execute(['stripe', 'test_evt_002', 'invoice.payment_succeeded']);
        // Second attempt — same event
        $stmt->execute(['stripe', 'test_evt_002', 'invoice.payment_succeeded']);
        $this->assertEquals(0, $stmt->rowCount());
    }
}
```

- [ ] **Step 9.2: Run test**

```bash
./vendor/bin/phpunit tests/WebhookIdempotencyTest.php
```

Expected: PASS (requires live saas_platform DB with webhook_events table).

- [ ] **Step 9.3: Create webhooks/mp.php**

`webhooks/mp.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\ProvisioningService;
use App\MailService;

http_response_code(200); // Always respond 200 to MP

$raw = file_get_contents('php://input');
$headers = getallheaders();

// Validate HMAC-SHA256 signature
$xSignature = $headers['X-Signature'] ?? '';
$xRequestId = $headers['X-Request-Id'] ?? '';
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$signedPayload = "id:{$_GET['id'] ?? ''};request-id:$xRequestId;ts:{$_GET['ts'] ?? ''}";
$expectedSig = hash_hmac('sha256', $signedPayload, $_ENV['MP_WEBHOOK_SECRET']);
// Note: MP signature format is "ts=...;v1=..." — extract v1 part
$parts = [];
foreach (explode(';', $xSignature) as $part) {
    [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
    $parts[$k] = $v;
}
if (!hash_equals($expectedSig, $parts['v1'] ?? '')) {
    error_log('[MP Webhook] Invalid signature');
    exit;
}

$payload = json_decode($raw, true);
$type    = $payload['type'] ?? '';
$eventId = $payload['id'] ?? '';
if (!$eventId) exit;

// Idempotency — atomic INSERT IGNORE
$pdo  = getPlatformPDO();
$stmt = $pdo->prepare('INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)');
$stmt->execute(['mercadopago', (string)$eventId, $type]);
if ($stmt->rowCount() === 0) exit; // Already processed

$svc = new ProvisioningService();

if ($type === 'subscription_preapproval') {
    $status = $payload['data']['status'] ?? '';
    $preapprovalId = $payload['data']['id'] ?? '';
    // Fetch preapproval to get external_reference
    $mpData = json_decode(file_get_contents(
        "https://api.mercadopago.com/preapproval/$preapprovalId",
        false,
        stream_context_create(['http' => ['header' => 'Authorization: Bearer ' . $_ENV['MP_ACCESS_TOKEN']]])
    ), true);
    $sessionToken = $mpData['external_reference'] ?? '';
    if ($status === 'authorized' && $sessionToken) {
        $cs = $pdo->prepare('SELECT * FROM checkout_sessions WHERE session_token = ?');
        $cs->execute([$sessionToken]);
        $session = $cs->fetch();
        if ($session && $session['estado'] === 'pendiente') {
            $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE session_token = ?')
                ->execute([$preapprovalId, $sessionToken]);
            $svc->provision($session['id']);
        }
    } elseif ($status === 'cancelled') {
        $sub = $pdo->prepare("SELECT * FROM subscriptions WHERE provider_subscription_id = ?");
        $sub->execute([$preapprovalId]);
        $subscription = $sub->fetch();
        if ($subscription) {
            $graceFin = date('Y-m-d H:i:s', strtotime('+3 days'));
            $pdo->prepare("UPDATE subscriptions SET estado='cancelada', grace_period_fin=? WHERE id=?")
                ->execute([$graceFin, $subscription['id']]);
            $tenant = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
            $tenant->execute([$subscription['tenant_id']]);
            $t = $tenant->fetch();
            MailService::send($t['email'], 'Problema con tu suscripción', 'grace_period', [
                'empresa'     => $t['nombre'],
                'grace_until' => $graceFin,
            ]);
        }
    }
} elseif ($type === 'subscription_authorized_payment') {
    $status = $payload['data']['status'] ?? '';
    $preapprovalId = $payload['data']['preapproval_id'] ?? '';
    if ($status === 'processed') {
        $sub = $pdo->prepare("SELECT tenant_id FROM subscriptions WHERE provider_subscription_id = ?");
        $sub->execute([$preapprovalId]);
        $s = $sub->fetch();
        if ($s) {
            $svc->updatePeriodFin($s['tenant_id'], new DateTime('+1 month'));
        }
    }
}
```

- [ ] **Step 9.4: Create webhooks/stripe.php**

`webhooks/stripe.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\ProvisioningService;
use App\MailService;
use Stripe\Webhook;
use Stripe\StripeClient;

$raw = file_get_contents('php://input');
try {
    $event = Webhook::constructEvent(
        $raw,
        $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '',
        $_ENV['STRIPE_WEBHOOK_SECRET']
    );
} catch (\Exception $e) {
    http_response_code(400);
    exit;
}

$pdo     = getPlatformPDO();
$eventId = $event->id;

// Idempotency — atomic INSERT IGNORE
$stmt = $pdo->prepare('INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)');
$stmt->execute(['stripe', $eventId, $event->type]);
if ($stmt->rowCount() === 0) {
    http_response_code(200);
    exit;
}

$svc    = new ProvisioningService();
$stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);

if ($event->type === 'invoice.payment_succeeded') {
    $invoice = $event->data->object;
    $subId   = $invoice->subscription;
    $reason  = $invoice->billing_reason;

    if ($reason === 'subscription_create') {
        // First payment — provision tenant
        $subscription = $stripe->subscriptions->retrieve($subId);
        $sessionToken = $subscription->metadata->session_token ?? '';
        if ($sessionToken) {
            $cs = $pdo->prepare('SELECT * FROM checkout_sessions WHERE session_token = ?');
            $cs->execute([$sessionToken]);
            $session = $cs->fetch();
            if ($session && $session['estado'] === 'pendiente') {
                $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE session_token = ?')
                    ->execute([$subId, $sessionToken]);
                $svc->provision($session['id']);
            }
        }
    } else {
        // Renewal
        $sub = $pdo->prepare("SELECT tenant_id FROM subscriptions WHERE provider_subscription_id = ?");
        $sub->execute([$subId]);
        $s = $sub->fetch();
        if ($s) {
            $periodEnd = new DateTime('@' . $invoice->lines->data[0]->period->end);
            $svc->updatePeriodFin($s['tenant_id'], $periodEnd);
        }
    }
} elseif ($event->type === 'invoice.payment_failed') {
    $invoice = $event->data->object;
    $subId   = $invoice->subscription;
    $sub     = $pdo->prepare("SELECT * FROM subscriptions WHERE provider_subscription_id = ?");
    $sub->execute([$subId]);
    $subscription = $sub->fetch();
    if ($subscription) {
        $newCount = $subscription['payment_failure_count'] + 1;
        $pdo->prepare("UPDATE subscriptions SET payment_failure_count = ? WHERE id = ?")
            ->execute([$newCount, $subscription['id']]);
        if ($newCount >= 3) {
            $graceFin = date('Y-m-d H:i:s', strtotime('+3 days'));
            $pdo->prepare("UPDATE subscriptions SET grace_period_fin = ? WHERE id = ?")->execute([$graceFin, $subscription['id']]);
            $tenant = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
            $tenant->execute([$subscription['tenant_id']]);
            $t = $tenant->fetch();
            MailService::send($t['email'], 'Problema con tu suscripción', 'grace_period', [
                'empresa'     => $t['nombre'],
                'grace_until' => $graceFin,
            ]);
        }
    }
} elseif ($event->type === 'customer.subscription.deleted') {
    $subId = $event->data->object->id;
    $sub   = $pdo->prepare("SELECT * FROM subscriptions WHERE provider_subscription_id = ?");
    $sub->execute([$subId]);
    $subscription = $sub->fetch();
    if ($subscription) {
        $graceFin = date('Y-m-d H:i:s', strtotime('+3 days'));
        $pdo->prepare("UPDATE subscriptions SET estado='cancelada', grace_period_fin=? WHERE id=?")
            ->execute([$graceFin, $subscription['id']]);
        $tenant = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
        $tenant->execute([$subscription['tenant_id']]);
        $t = $tenant->fetch();
        MailService::send($t['email'], 'Suscripción cancelada', 'cancelacion', [
            'empresa'    => $t['nombre'],
            'periodo_fin' => $subscription['periodo_fin'],
        ]);
    }
}

// Mark event as fully processed
$pdo->prepare('UPDATE webhook_events SET procesado = 1 WHERE provider = ? AND provider_event_id = ?')
    ->execute(['stripe', $eventId]);

http_response_code(200);
```

- [ ] **Step 9.5: Commit**

```bash
git add webhooks/ tests/WebhookIdempotencyTest.php
git commit -m "feat: add MP and Stripe webhook handlers with idempotency"
```

---

## Task 10: Landing Page + Checkout

**Files:**
- Create: `pedidos-platform/landing/index.php`
- Create: `pedidos-platform/landing/checkout.php`

- [ ] **Step 10.1: Create landing/index.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
$plans = getPlatformPDO()->query('SELECT * FROM plans WHERE activo = 1 ORDER BY precio_ars ASC')->fetchAll();
$pageTitle = 'Planes';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2 class="mb-4">Elegí tu plan</h2>
<div class="row g-4">
<?php foreach ($plans as $plan): ?>
<div class="col-md-4">
  <div class="card h-100 shadow-sm">
    <div class="card-body">
      <h5 class="card-title"><?= htmlspecialchars($plan['nombre']) ?></h5>
      <p class="display-6">$<?= number_format($plan['precio_ars'], 0, ',', '.') ?> ARS</p>
      <p class="text-muted">USD <?= number_format($plan['precio_usd'], 2) ?></p>
    </div>
    <div class="card-footer bg-transparent">
      <a href="checkout.php?plan=<?= $plan['id'] ?>" class="btn btn-primary w-100">Contratar</a>
    </div>
  </div>
</div>
<?php endforeach ?>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 10.2: Create landing/checkout.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

$pdo    = getPlatformPDO();
$planId = (int)($_GET['plan'] ?? 0);
$plan   = $pdo->prepare('SELECT * FROM plans WHERE id = ? AND activo = 1');
$plan->execute([$planId]);
$planRow = $plan->fetch();
if (!$planRow) { header('Location: ' . APP_URL . '/landing/'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $empresa  = trim($_POST['empresa'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $provider = $_POST['provider'] ?? '';

    if (!$nombre || !$empresa || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($provider, ['mercadopago', 'stripe'])) {
        $error = 'Por favor completá todos los campos correctamente.';
    } else {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare(
            'INSERT INTO checkout_sessions (session_token, nombre, empresa, email, plan_id, provider) VALUES (?,?,?,?,?,?)'
        )->execute([$token, $nombre, $empresa, $email, $planId, $provider]);
        $sessionId = (int)$pdo->lastInsertId();

        if ($provider === 'stripe') {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $priceId = $planRow['stripe_price_id'] ?? null; // Set this in plans table or env
            $checkout = \Stripe\Checkout\Session::create([
                'mode'            => 'subscription',
                'payment_method_types' => ['card'],
                'line_items'      => [['price' => $priceId, 'quantity' => 1]],
                'success_url'     => APP_URL . '/landing/success.php?token=' . $token,
                'cancel_url'      => APP_URL . '/landing/?canceled=1',
                'subscription_data' => ['metadata' => ['session_token' => $token]],
                'customer_email'  => $email,
            ]);
            $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE id = ?')
                ->execute([$checkout->id, $sessionId]);
            header('Location: ' . $checkout->url);
            exit;
        } else {
            // MercadoPago Preapproval
            $payload = [
                'reason'             => $planRow['nombre'],
                'auto_recurring'     => [
                    'frequency'      => 1,
                    'frequency_type' => 'months',
                    'transaction_amount' => (float)$planRow['precio_ars'],
                    'currency_id'    => 'ARS',
                ],
                'payer_email'        => $email,
                'external_reference' => $token,
                'back_url'           => APP_URL . '/landing/success.php?token=' . $token,
            ];
            $ch = curl_init('https://api.mercadopago.com/preapproval');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $_ENV['MP_ACCESS_TOKEN'],
                ],
            ]);
            $resp   = json_decode(curl_exec($ch), true);
            curl_close($ch);
            $initPoint = $resp['init_point'] ?? null;
            if ($initPoint) {
                $pdo->prepare('UPDATE checkout_sessions SET provider_ref = ? WHERE id = ?')
                    ->execute([$resp['id'] ?? '', $sessionId]);
                header('Location: ' . $initPoint);
                exit;
            }
            $error = 'Error al crear la suscripción. Intentá nuevamente.';
        }
    }
}

$pageTitle = 'Checkout';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2 class="mb-4">Suscribirse — <?= htmlspecialchars($planRow['nombre']) ?></h2>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
<form method="post" style="max-width:480px">
  <div class="mb-3"><label>Nombre completo</label>
    <input type="text" name="nombre" class="form-control" required></div>
  <div class="mb-3"><label>Nombre de tu empresa</label>
    <input type="text" name="empresa" class="form-control" required></div>
  <div class="mb-3"><label>Email</label>
    <input type="email" name="email" class="form-control" required></div>
  <div class="mb-3"><label>Método de pago</label>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="provider" value="mercadopago" id="mp" checked>
      <label class="form-check-label" for="mp">MercadoPago (ARS)</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="provider" value="stripe" id="st">
      <label class="form-check-label" for="st">Stripe (USD)</label>
    </div>
  </div>
  <button type="submit" class="btn btn-success">Ir al pago &rarr;</button>
</form>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 10.3: Create landing/success.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
$pageTitle = 'Pago recibido';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="text-center py-5">
  <h2>¡Gracias por tu suscripción!</h2>
  <p class="lead">Estamos procesando tu pago. Recibirás un email con tus credenciales de acceso en los próximos minutos.</p>
  <a href="<?= APP_URL ?>/landing/" class="btn btn-outline-primary mt-3">Volver al inicio</a>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 10.4: Commit**

```bash
git add landing/
git commit -m "feat: add landing page, checkout flow, and success page"
```

---

## Task 11: Cron Job (Grace Period Enforcement)

**Files:**
- Create: `pedidos-platform/cli/suspend_expired.php`

- [ ] **Step 11.1: Create suspend_expired.php**

`cli/suspend_expired.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\ProvisioningService;

$pdo  = getPlatformPDO();
$svc  = new ProvisioningService();

$stmt = $pdo->query(
    "SELECT s.tenant_id FROM subscriptions s
     JOIN tenants t ON t.id = s.tenant_id
     WHERE s.grace_period_fin IS NOT NULL
       AND s.grace_period_fin <= NOW()
       AND t.estado = 'activo'
       AND t.deleted_at IS NULL"
);

$tenantIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
foreach ($tenantIds as $tenantId) {
    try {
        $svc->suspend((int)$tenantId);
        $pdo->prepare("UPDATE subscriptions SET grace_period_fin = NULL WHERE tenant_id = ?")
            ->execute([$tenantId]);
        echo "Suspended tenant $tenantId\n";
    } catch (\Throwable $e) {
        error_log("[suspend_expired] tenant $tenantId FAIL: " . $e->getMessage());
    }
}
echo "Done. Processed " . count($tenantIds) . " tenant(s).\n";
```

- [ ] **Step 11.2: Add cron entry**

Add to crontab (`crontab -e`):
```
* * * * * php /path/to/pedidos-platform/cli/suspend_expired.php >> /var/log/pp_cron.log 2>&1
```

Replace `/path/to/pedidos-platform` with the actual path.

- [ ] **Step 11.3: Commit**

```bash
git add cli/
git commit -m "feat: add cron job to suspend tenants past grace period"
```

---

## Task 12: Superadmin — Dashboard + Tenant Detail

**Files:**
- Create: `pedidos-platform/superadmin/index.php`
- Create: `pedidos-platform/superadmin/dashboard.php`
- Create: `pedidos-platform/superadmin/tenant.php`
- Create: `pedidos-platform/superadmin/tenant_save.php`
- Create: `pedidos-platform/superadmin/activate.php`

- [ ] **Step 12.1: Create superadmin/index.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
header('Location: ' . APP_URL . '/superadmin/dashboard.php');
exit;
```

- [ ] **Step 12.2: Create superadmin/dashboard.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();

$pdo     = getPlatformPDO();
$tenants = $pdo->query(
    "SELECT t.*, p.nombre AS plan_nombre, s.periodo_fin, s.estado AS sub_estado
     FROM tenants t
     JOIN plans p ON p.id = t.plan_id
     LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.deleted_at IS NULL
     WHERE t.deleted_at IS NULL
     ORDER BY t.created_at DESC"
)->fetchAll();

$pending = array_filter($tenants, fn($t) => $t['estado'] === 'pendiente_whatsapp');

$pageTitle    = 'Dashboard';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Tenants <?php if (count($pending)): ?>
  <span class="badge bg-warning text-dark"><?= count($pending) ?> pendiente(s) WhatsApp</span>
<?php endif ?></h2>
<table class="table table-striped mt-3">
  <thead><tr>
    <th>Empresa</th><th>Email</th><th>Plan</th><th>Estado</th><th>Sub</th><th>Vence</th><th></th>
  </tr></thead>
  <tbody>
  <?php foreach ($tenants as $t): ?>
  <tr>
    <td><?= htmlspecialchars($t['nombre']) ?></td>
    <td><?= htmlspecialchars($t['email']) ?></td>
    <td><?= htmlspecialchars($t['plan_nombre']) ?></td>
    <td><span class="badge bg-<?= match($t['estado']) {
      'activo' => 'success', 'suspendido' => 'danger',
      'pendiente_whatsapp' => 'warning', default => 'secondary'} ?>">
      <?= htmlspecialchars($t['estado']) ?></span></td>
    <td><?= htmlspecialchars($t['sub_estado'] ?? '-') ?></td>
    <td><?= $t['periodo_fin'] ? date('d/m/Y', strtotime($t['periodo_fin'])) : '-' ?></td>
    <td><a href="tenant.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 12.3: Create superadmin/tenant.php (detail + WhatsApp form)**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\Encryption;
Auth::requireSuperadmin();

$pdo      = getPlatformPDO();
$tenantId = (int)($_GET['id'] ?? 0);
$stmt     = $pdo->prepare('SELECT * FROM tenants WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();
if (!$tenant) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

$sub = $pdo->prepare('SELECT * FROM subscriptions WHERE tenant_id = ? AND deleted_at IS NULL');
$sub->execute([$tenantId]);
$subscription = $sub->fetch();

$success = $_GET['saved'] ?? '';

$pageTitle    = 'Tenant: ' . $tenant['nombre'];
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2><?= htmlspecialchars($tenant['nombre']) ?>
  <span class="badge bg-secondary"><?= htmlspecialchars($tenant['estado']) ?></span>
</h2>
<p class="text-muted"><?= htmlspecialchars($tenant['email']) ?> · Slug: <code><?= htmlspecialchars($tenant['slug']) ?></code></p>

<?php if ($success === '1'): ?><div class="alert alert-success">Credenciales guardadas.</div><?php endif ?>

<h5 class="mt-4">Credenciales de WhatsApp</h5>
<form method="post" action="tenant_save.php">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
  <div class="mb-3"><label>Phone Number ID</label>
    <input type="text" name="phone_id" class="form-control" value="<?= htmlspecialchars($tenant['whatsapp_phone_id'] ?? '') ?>"></div>
  <div class="mb-3"><label>WABA ID</label>
    <input type="text" name="waba_id" class="form-control" value="<?= htmlspecialchars($tenant['whatsapp_waba_id'] ?? '') ?>"></div>
  <div class="mb-3"><label>Access Token</label>
    <?php if ($tenant['whatsapp_token_enc']): ?>
      <div class="input-group">
        <input type="text" class="form-control" value="<?= Encryption::mask(Encryption::decrypt($tenant['whatsapp_token_enc'])) ?>" readonly id="tokenMasked">
        <input type="password" name="token" class="form-control d-none" id="tokenInput" placeholder="Nuevo token (dejar vacío para no cambiar)">
        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('tokenMasked').classList.toggle('d-none');document.getElementById('tokenInput').classList.toggle('d-none')">Editar</button>
      </div>
    <?php else: ?>
      <input type="password" name="token" class="form-control" placeholder="Access Token de Meta">
    <?php endif ?>
  </div>
  <button type="submit" class="btn btn-primary">Guardar credenciales</button>
</form>

<div class="mt-4 d-flex gap-2">
<?php if ($tenant['estado'] === 'pendiente_whatsapp' && $tenant['whatsapp_phone_id'] && $tenant['whatsapp_waba_id'] && $tenant['whatsapp_token_enc']): ?>
  <form method="post" action="activate.php">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
    <button type="submit" class="btn btn-success">Activar tenant</button>
  </form>
<?php endif ?>
<?php if ($tenant['estado'] === 'activo'): ?>
  <form method="post" action="suspend.php">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
    <button type="submit" class="btn btn-warning" onclick="return confirm('¿Suspender?')">Suspender</button>
  </form>
<?php elseif ($tenant['estado'] === 'suspendido'): ?>
  <form method="post" action="reactivate.php">
    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
    <button type="submit" class="btn btn-success">Reactivar</button>
  </form>
  <?php if ($subscription && $subscription['estado'] === 'cancelada'): ?>
  <a href="teardown.php?id=<?= $tenantId ?>" class="btn btn-danger">Teardown</a>
  <?php endif ?>
<?php endif ?>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 12.4: Create tenant_save.php, activate.php, suspend.php, reactivate.php**

`superadmin/tenant_save.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\Encryption;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$pdo      = getPlatformPDO();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
$phoneId  = trim($_POST['phone_id'] ?? '');
$wabaId   = trim($_POST['waba_id'] ?? '');
$token    = trim($_POST['token'] ?? '');

$updates  = 'whatsapp_phone_id = ?, whatsapp_waba_id = ?';
$params   = [$phoneId, $wabaId];
if ($token !== '') {
    $updates .= ', whatsapp_token_enc = ?';
    $params[] = Encryption::encrypt($token);
    // Also update axbot.empresa.telefono if phone changed
    $slugStmt = $pdo->prepare("SELECT slug FROM tenants WHERE id = ?");
    $slugStmt->execute([$tenantId]);
    $slug = $slugStmt->fetchColumn();
    getAxbotPDO()->prepare('UPDATE empresa SET telefono = ? WHERE empresa = ?')->execute([$phoneId, $slug]);
}
$params[] = $tenantId;
$pdo->prepare("UPDATE tenants SET $updates WHERE id = ?")->execute($params);
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId . '&saved=1');
exit;
```

`superadmin/activate.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\MailService;
Auth::requireSuperadmin();
Auth::verifyCsrf();

$pdo      = getPlatformPDO();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
$stmt     = $pdo->prepare('SELECT * FROM tenants WHERE id = ? AND estado = ?');
$stmt->execute([$tenantId, 'pendiente_whatsapp']);
$tenant = $stmt->fetch();
if ($tenant && $tenant['whatsapp_phone_id'] && $tenant['whatsapp_waba_id'] && $tenant['whatsapp_token_enc']) {
    $pdo->prepare("UPDATE tenants SET estado = 'activo' WHERE id = ?")->execute([$tenantId]);
    getAxbotPDO()->prepare("UPDATE empresa SET activo = 1 WHERE empresa = ?")->execute([$tenant['slug']]);
    MailService::send($tenant['email'], '¡Tu cuenta está activa!', 'activacion', ['empresa' => $tenant['nombre']]);
}
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId);
exit;
```

`superadmin/suspend.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\ProvisioningService;
Auth::requireSuperadmin();
Auth::verifyCsrf();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
(new ProvisioningService())->suspend($tenantId);
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId);
exit;
```

`superadmin/reactivate.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\ProvisioningService;
Auth::requireSuperadmin();
Auth::verifyCsrf();
$tenantId = (int)($_POST['tenant_id'] ?? 0);
(new ProvisioningService())->reactivate($tenantId);
header('Location: ' . APP_URL . '/superadmin/tenant.php?id=' . $tenantId);
exit;
```

- [ ] **Step 12.5: Create teardown.php**

`superadmin/teardown.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\ProvisioningService;
Auth::requireSuperadmin();

$pdo      = getPlatformPDO();
$tenantId = (int)($_GET['id'] ?? $_POST['tenant_id'] ?? 0);
$stmt     = $pdo->prepare('SELECT slug, nombre FROM tenants WHERE id = ?');
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();
if (!$tenant) { header('Location: ' . APP_URL . '/superadmin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    if ($_POST['confirm_slug'] !== $tenant['slug']) {
        $error = 'El slug ingresado no coincide.';
    } else {
        (new ProvisioningService())->teardown($tenantId);
        header('Location: ' . APP_URL . '/superadmin/dashboard.php?torn=1');
        exit;
    }
}

$pageTitle = 'Teardown';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<div class="card border-danger" style="max-width:480px">
  <div class="card-header bg-danger text-white">Teardown — <?= htmlspecialchars($tenant['nombre']) ?></div>
  <div class="card-body">
    <p>Esta acción es <strong>irreversible</strong>. Eliminará la base de datos del tenant y todos sus datos.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
      <div class="mb-3"><label>Escribí el slug <code><?= htmlspecialchars($tenant['slug']) ?></code> para confirmar</label>
        <input type="text" name="confirm_slug" class="form-control" required></div>
      <button type="submit" class="btn btn-danger">Confirmar teardown</button>
      <a href="tenant.php?id=<?= $tenantId ?>" class="btn btn-secondary ms-2">Cancelar</a>
    </form>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 12.6: Manual test**

Log in as superadmin → visit dashboard → click "Ver" on a pending tenant → verify WhatsApp form renders → save credentials → activate.

- [ ] **Step 12.7: Commit**

```bash
git add superadmin/
git commit -m "feat: add superadmin dashboard, tenant detail, lifecycle actions"
```

---

## Task 13: Superadmin — Planes CRUD

**Files:**
- Create: `pedidos-platform/superadmin/planes.php`

- [ ] **Step 13.1: Create planes.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
$pdo = getPlatformPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id        = (int)($_POST['id'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $precioArs = (float)($_POST['precio_ars'] ?? 0);
        $precioUsd = (float)($_POST['precio_usd'] ?? 0);
        $activo    = isset($_POST['activo']) ? 1 : 0;
        if ($id) {
            $pdo->prepare('UPDATE plans SET nombre=?, precio_ars=?, precio_usd=?, activo=? WHERE id=?')
                ->execute([$nombre, $precioArs, $precioUsd, $activo, $id]);
        } else {
            $pdo->prepare('INSERT INTO plans (nombre, precio_ars, precio_usd, activo) VALUES (?,?,?,?)')
                ->execute([$nombre, $precioArs, $precioUsd, $activo]);
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE plans SET activo = 1 - activo WHERE id = ?')->execute([$id]);
    }
    header('Location: ' . APP_URL . '/superadmin/planes.php');
    exit;
}

$plans = $pdo->query('SELECT * FROM plans ORDER BY id')->fetchAll();
$edit  = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$pageTitle    = 'Planes';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Planes</h2>
<table class="table mt-3">
  <thead><tr><th>Nombre</th><th>ARS</th><th>USD</th><th>Activo</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($plans as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['nombre']) ?></td>
    <td>$<?= number_format($p['precio_ars'], 2) ?></td>
    <td>$<?= number_format($p['precio_usd'], 2) ?></td>
    <td><?= $p['activo'] ? '✓' : '—' ?></td>
    <td>
      <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <button class="btn btn-sm btn-outline-secondary"><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<h5 class="mt-4"><?= $edit ? 'Editar plan' : 'Nuevo plan' ?></h5>
<form method="post" style="max-width:400px">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
  <div class="mb-2"><input type="text" name="nombre" class="form-control" placeholder="Nombre" value="<?= htmlspecialchars($edit['nombre'] ?? '') ?>" required></div>
  <div class="mb-2"><input type="number" name="precio_ars" step="0.01" class="form-control" placeholder="Precio ARS" value="<?= $edit['precio_ars'] ?? '' ?>" required></div>
  <div class="mb-2"><input type="number" name="precio_usd" step="0.01" class="form-control" placeholder="Precio USD" value="<?= $edit['precio_usd'] ?? '' ?>" required></div>
  <div class="mb-2 form-check"><input type="checkbox" class="form-check-input" name="activo" id="activo" <?= ($edit['activo'] ?? 1) ? 'checked' : '' ?>>
    <label class="form-check-label" for="activo">Activo (visible en landing)</label></div>
  <button type="submit" class="btn btn-primary">Guardar</button>
</form>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 13.2: Add subscription history view to superadmin**

Add `superadmin/suscripciones.php`:
```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();

$pdo      = getPlatformPDO();
$tenantId = (int)($_GET['tenant'] ?? 0);
$where    = $tenantId ? 'WHERE s.tenant_id = ' . $tenantId : '';
$subs     = $pdo->query(
    "SELECT s.*, t.nombre AS empresa FROM subscriptions s
     JOIN tenants t ON t.id = s.tenant_id
     $where ORDER BY s.created_at DESC LIMIT 100"
)->fetchAll();

$pageTitle    = 'Suscripciones';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Historial de suscripciones</h2>
<table class="table mt-3">
  <thead><tr><th>Empresa</th><th>Proveedor</th><th>Estado</th><th>Vence</th><th>Reintentos</th><th>Alta</th></tr></thead>
  <tbody>
  <?php foreach ($subs as $s): ?>
  <tr>
    <td><?= htmlspecialchars($s['empresa']) ?></td>
    <td><?= htmlspecialchars($s['provider']) ?></td>
    <td><?= htmlspecialchars($s['estado']) ?></td>
    <td><?= $s['periodo_fin'] ? date('d/m/Y', strtotime($s['periodo_fin'])) : '-' ?></td>
    <td><?= $s['payment_failure_count'] ?></td>
    <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

Also add the nav link in `views/layout/header.php` for superadmin: add `<a href="<?= APP_URL ?>/superadmin/suscripciones.php" class="text-white text-decoration-none">Suscripciones</a>` to the superadmin nav block.

- [ ] **Step 13.3: Commit**

```bash
git add superadmin/planes.php superadmin/suscripciones.php views/layout/header.php
git commit -m "feat: add plans CRUD and subscription history in superadmin"
```

---

## Task 14: Tenant Portal — Bot Editor

**Files:**
- Create: `pedidos-platform/portal/index.php`
- Create: `pedidos-platform/portal/bot.php`

- [ ] **Step 14.1: Create portal/index.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireTenantAdmin();
header('Location: ' . APP_URL . '/portal/bot.php');
exit;
```

- [ ] **Step 14.2: Create portal/bot.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireTenantAdmin();

$tenantId = $_SESSION['tenant_id'];
$pdo      = getPlatformPDO();
$stmt     = $pdo->prepare('SELECT slug FROM tenants WHERE id = ?');
$stmt->execute([$tenantId]);
$tenant   = $stmt->fetch();

$axbot = getAxbotPDO();
$row   = $axbot->prepare('SELECT json FROM empresa WHERE empresa = ?');
$row->execute([$tenant['slug']]);
$empresa = $row->fetch();
$menuJson = json_decode($empresa['json'] ?? '[]', true) ?: [];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $updated = $menuJson;
    foreach ($_POST['consigna'] ?? [] as $menuId => $consigna) {
        foreach ($updated as &$item) {
            if ((string)$item['menuId'] === (string)$menuId) {
                $item['consigna'] = $consigna;
            }
        }
    }
    // Validate required keys in each entry
    $valid = true;
    foreach ($updated as $item) {
        if (!isset($item['menuId'], $item['consigna'], $item['finaliza'])) {
            $valid = false;
            break;
        }
    }
    if (!$valid) {
        $error = 'El JSON del bot resultante es inválido. No se guardaron los cambios.';
    } else {
        $axbot->prepare('UPDATE empresa SET json = ? WHERE empresa = ?')
              ->execute([json_encode($updated, JSON_UNESCAPED_UNICODE), $tenant['slug']]);
        $menuJson = $updated;
        $success = 'Menú del bot actualizado correctamente.';
    }
}

$pageTitle    = 'Editor del Bot';
$activeModule = 'portal';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Editor del bot</h2>
<p class="text-muted">Editá los textos de los mensajes que tu bot envía a los clientes.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <?php foreach ($menuJson as $item): ?>
    <?php if (trim($item['consigna']) === '') continue; ?>
    <div class="mb-3">
      <label class="form-label fw-semibold">Mensaje nodo <code><?= htmlspecialchars($item['menuId']) ?></code></label>
      <textarea name="consigna[<?= htmlspecialchars($item['menuId']) ?>]" class="form-control" rows="2"><?= htmlspecialchars($item['consigna']) ?></textarea>
    </div>
  <?php endforeach ?>
  <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 14.3: Commit**

```bash
git add portal/index.php portal/bot.php
git commit -m "feat: add tenant portal bot editor with JSON validation"
```

---

## Task 15: Tenant Portal — User Management

**Files:**
- Create: `pedidos-platform/portal/usuarios.php`

The tenant portal manages `usuario` table in the tenant's `wb_{slug}` database.

- [ ] **Step 15.1: Create portal/usuarios.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireTenantAdmin();

$tenantId = $_SESSION['tenant_id'];
$pdo      = getPlatformPDO();
$stmt     = $pdo->prepare('SELECT db_name FROM tenants WHERE id = ?');
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

// Connect to tenant's own DB
$dsn        = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $tenant['db_name']);
$tenantPdo  = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $login  = trim($_POST['login'] ?? '');
        $clave  = trim($_POST['clave'] ?? '');
        $cargo  = trim($_POST['cargo'] ?? 'Operador');
        if ($nombre && $login && $clave) {
            try {
                $tenantPdo->prepare(
                    'INSERT INTO usuario (nombre, tipo_documento, num_documento, login, clave, imagen, condicion, cargo) VALUES (?,?,?,?,?,?,1,?)'
                )->execute([$nombre, 'DNI', '0', $login, $clave, '', $cargo]);
                $success = 'Usuario creado.';
            } catch (\PDOException $e) {
                $error = 'El login ya existe o hubo un error.';
            }
        } else {
            $error = 'Completá todos los campos.';
        }
    } elseif ($action === 'toggle') {
        $id     = (int)($_POST['user_id'] ?? 0);
        $tenantPdo->prepare('UPDATE usuario SET condicion = 1 - condicion WHERE idusuario = ?')->execute([$id]);
    }
}

$usuarios = $tenantPdo->query('SELECT idusuario, nombre, login, cargo, condicion FROM usuario ORDER BY nombre')->fetchAll();

$pageTitle    = 'Usuarios';
$activeModule = 'portal';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Usuarios</h2>
<table class="table mt-3">
  <thead><tr><th>Nombre</th><th>Login</th><th>Cargo</th><th>Estado</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($usuarios as $u): ?>
  <tr>
    <td><?= htmlspecialchars($u['nombre']) ?></td>
    <td><?= htmlspecialchars($u['login']) ?></td>
    <td><?= htmlspecialchars($u['cargo']) ?></td>
    <td><?= $u['condicion'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
    <td>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="user_id" value="<?= $u['idusuario'] ?>">
        <button class="btn btn-sm btn-outline-secondary"><?= $u['condicion'] ? 'Desactivar' : 'Activar' ?></button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<h5 class="mt-4">Agregar usuario</h5>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif ?>
<form method="post" style="max-width:400px">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="action" value="create">
  <div class="mb-2"><input type="text" name="nombre" class="form-control" placeholder="Nombre completo" required></div>
  <div class="mb-2"><input type="text" name="login" class="form-control" placeholder="Usuario (login)" required></div>
  <div class="mb-2"><input type="text" name="clave" class="form-control" placeholder="Contraseña" required></div>
  <div class="mb-2"><input type="text" name="cargo" class="form-control" placeholder="Cargo (ej: Operador)" value="Operador"></div>
  <button type="submit" class="btn btn-primary">Crear usuario</button>
</form>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 15.2: Commit**

```bash
git add portal/usuarios.php
git commit -m "feat: add portal user management for tenant DB"
```

---

## Task 16: Tenant Portal — Subscription View + Cancel

**Files:**
- Create: `pedidos-platform/portal/suscripcion.php`
- Create: `pedidos-platform/modelos/BillingService.php`

- [ ] **Step 16.1: Create BillingService.php**

`modelos/BillingService.php`:
```php
<?php
namespace App;

use Stripe\StripeClient;

class BillingService
{
    public function cancelStripe(string $subscriptionId): void
    {
        $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);
        $stripe->subscriptions->cancel($subscriptionId, ['prorate' => false]);
    }

    public function cancelMercadoPago(string $preapprovalId): void
    {
        $ch = curl_init("https://api.mercadopago.com/preapproval/$preapprovalId");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => json_encode(['status' => 'cancelled']),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $_ENV['MP_ACCESS_TOKEN'],
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
```

- [ ] **Step 16.2: Create portal/suscripcion.php**

```php
<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
use App\BillingService;
use App\MailService;
Auth::requireTenantAdmin();

$tenantId = $_SESSION['tenant_id'];
$pdo      = getPlatformPDO();
$subStmt  = $pdo->prepare(
    'SELECT s.*, p.nombre AS plan_nombre, p.precio_ars, p.precio_usd
     FROM subscriptions s
     JOIN tenants t ON t.id = s.tenant_id
     JOIN plans p ON p.id = t.plan_id
     WHERE s.tenant_id = ? AND s.deleted_at IS NULL
     ORDER BY s.id DESC LIMIT 1'
);
$subStmt->execute([$tenantId]);
$sub = $subStmt->fetch();

$tenantStmt = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
$tenantStmt->execute([$tenantId]);
$tenant = $tenantStmt->fetch();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    if ($_POST['action'] === 'cancel' && $sub && $sub['estado'] === 'activa') {
        $billing = new BillingService();
        try {
            if ($sub['provider'] === 'stripe') {
                $billing->cancelStripe($sub['provider_subscription_id']);
            } else {
                $billing->cancelMercadoPago($sub['provider_subscription_id']);
            }
            $pdo->prepare("UPDATE subscriptions SET estado = 'cancelada' WHERE id = ?")
                ->execute([$sub['id']]);
            MailService::send($tenant['email'], 'Suscripción cancelada', 'cancelacion', [
                'empresa'     => $tenant['nombre'],
                'periodo_fin' => date('d/m/Y', strtotime($sub['periodo_fin'])),
            ]);
            $success = 'Suscripción cancelada. Tu cuenta permanece activa hasta ' . date('d/m/Y', strtotime($sub['periodo_fin'])) . '.';
            $sub['estado'] = 'cancelada';
        } catch (\Throwable $e) {
            $error = 'No se pudo cancelar. Contactá soporte.';
        }
    }
}

$pageTitle    = 'Mi Suscripción';
$activeModule = 'portal';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Mi Suscripción</h2>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif ?>

<?php if ($sub): ?>
<?php if ($sub['grace_period_fin'] && strtotime($sub['grace_period_fin']) > time()): ?>
<div class="alert alert-warning">
  ⚠️ Hay un problema con tu pago. Tu cuenta se suspenderá el <?= date('d/m/Y', strtotime($sub['grace_period_fin'])) ?>.
</div>
<?php endif ?>
<table class="table" style="max-width:500px">
  <tr><th>Plan</th><td><?= htmlspecialchars($sub['plan_nombre']) ?></td></tr>
  <tr><th>Precio</th><td>$<?= number_format($sub['precio_ars'], 0, ',', '.') ?> ARS / USD <?= number_format($sub['precio_usd'], 2) ?></td></tr>
  <tr><th>Estado</th><td><?= htmlspecialchars($sub['estado']) ?></td></tr>
  <tr><th>Próximo cobro</th><td><?= date('d/m/Y', strtotime($sub['periodo_fin'])) ?></td></tr>
</table>

<?php if ($sub['estado'] === 'activa'): ?>
<form method="post" onsubmit="return confirm('¿Estás seguro? Cancelar la suscripción desactivará tu cuenta al vencer el período pago.')">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="action" value="cancel">
  <button type="submit" class="btn btn-outline-danger">Cancelar suscripción</button>
</form>
<?php endif ?>
<?php else: ?>
<p class="text-muted">No se encontró información de suscripción.</p>
<?php endif ?>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
```

- [ ] **Step 16.3: Commit**

```bash
git add portal/suscripcion.php modelos/BillingService.php
git commit -m "feat: add portal subscription view with cancel flow"
```

---

## Task 17: Phase 5 — Atiende Integration (WA Token)

This is the **only modification** to the existing Atiende app. The goal: read `WA_ACCESS_TOKEN` from `saas_platform.tenants` instead of `config/global.php`.

**Files to modify in the existing `whatsbus2021-main/` project:**
- Modify: `config/global.php` (or the file that defines `WA_ACCESS_TOKEN`)
- Modify: `config/WhatsAppClient.php` (if it uses the constant)

- [ ] **Step 17.1: Inspect how WA_ACCESS_TOKEN is used in Atiende**

```bash
grep -r "WA_ACCESS_TOKEN" ../whatsbus2021-main/ --include="*.php"
grep -r "whatsapp_token" ../whatsbus2021-main/ --include="*.php"
```

Review the output to understand the exact constant/variable name and where it's defined.

- [ ] **Step 17.2: Add a helper function to config/global.php**

In the existing Atiende app's `config/global.php`, add after the existing constants:

```php
// Fetch WhatsApp access token from saas_platform if not set in env
if (!defined('WA_ACCESS_TOKEN') || WA_ACCESS_TOKEN === '') {
    try {
        $dsn = sprintf('mysql:host=%s;dbname=saas_platform;charset=utf8', DB_HOST);
        $ppdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // DB_NAME is 'wb_{slug}' in the centralized model; extract the slug by stripping the prefix
        $slug = str_replace('wb_', '', DB_NAME);
        $stmt = $ppdo->prepare('SELECT whatsapp_token_enc FROM tenants WHERE slug = ? AND deleted_at IS NULL');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['whatsapp_token_enc']) {
            // Decrypt using the same key
            $key        = hex2bin(getenv('PLATFORM_ENCRYPTION_KEY'));
            $raw        = base64_decode($row['whatsapp_token_enc']);
            $iv         = substr($raw, 0, 12);
            $tag        = substr($raw, 12, 16);
            $ciphertext = substr($raw, 28);
            $token      = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            define('WA_ACCESS_TOKEN', $token ?: '');
        }
    } catch (Throwable $e) {
        define('WA_ACCESS_TOKEN', '');
        error_log('[global.php] Failed to fetch WA token from saas_platform: ' . $e->getMessage());
    }
}
```

**Note:** `PLATFORM_ENCRYPTION_KEY` must be added to `config/global.php` as well, loaded from environment.

- [ ] **Step 17.3: Test the integration**

Run the Atiende app. Send a WhatsApp message to the bot. Verify the bot responds (meaning it fetched the token successfully from `saas_platform`).

Check `error_log` for any `[global.php]` errors.

- [ ] **Step 17.4: Commit in the Atiende project**

```bash
cd ../whatsbus2021-main
git add config/global.php
git commit -m "feat: read WA_ACCESS_TOKEN from saas_platform.tenants instead of config"
```

---

## Run All Tests

After all tasks are complete:

```bash
cd pedidos-platform
./vendor/bin/phpunit --testdox
```

Expected output:
```
Encryption
 ✔ Encrypt then decrypt returns original
 ✔ Two encryptions produce different ciphertext
 ✔ Decrypt with wrong key throws
 ✔ Mask shows last eight chars

ProvisioningService
 ✔ Generate slug from company name
 ✔ Generate slug strips leading numbers

WebhookIdempotency
 ✔ First insert returns one affected row
 ✔ Duplicate insert returns zero affected rows
```
