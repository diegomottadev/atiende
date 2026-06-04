-- Run the following as MariaDB root before using this app:
-- CREATE USER 'pp_app'@'%' IDENTIFIED BY 'changeme';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON pedidos_platform.* TO 'pp_app'@'%';
-- FLUSH PRIVILEGES;
--
-- CREATE USER 'pp_provisioner'@'%' IDENTIFIED BY 'changeme';
-- GRANT CREATE, DROP, SELECT, INSERT, UPDATE ON `atiende_%`.* TO 'pp_provisioner'@'%';
-- GRANT SELECT ON pedidos_platform.* TO 'pp_provisioner'@'%';
-- FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS `pedidos_platform`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `pedidos_platform`;

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
  `whatsapp_app_secret_enc` TEXT NULL DEFAULT NULL,
  `tenant_mode` ENUM('mix','b2b','b2c') NOT NULL DEFAULT 'b2b',
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

-- Registro durable de fallos de provisioning para reconciliación.
-- compensated=0 = la compensación quedó incompleta → hay DB/registro huérfano
-- que limpiar con cli/reconcile_provisioning.php.
CREATE TABLE `provisioning_failures` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `checkout_session_id` INT UNSIGNED NULL DEFAULT NULL,
  `slug` VARCHAR(50) NULL DEFAULT NULL,
  `db_name` VARCHAR(60) NULL DEFAULT NULL,
  `paso` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error` TEXT NULL DEFAULT NULL,
  `compensated` TINYINT(1) NOT NULL DEFAULT 0,
  `compensate_error` TEXT NULL DEFAULT NULL,
  `resolved_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pendientes` (`compensated`, `resolved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Superadmin user (change password after first login)
-- Password: admin1234 (bcrypt)
INSERT INTO `platform_users` (`tenant_id`, `email`, `password_hash`, `rol`)
VALUES (NULL, 'admin@pedidos.app', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');
