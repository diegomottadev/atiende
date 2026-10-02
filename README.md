# Atiende

> Your WhatsApp takes care of customers, takes orders and keeps your business organized.

**Atiende** is a multi-tenant platform that turns a business's WhatsApp into an automated customer service and sales channel: a bot chats with customers, takes orders, complaints and inquiries, and everything is recorded in a web management dashboard.

## How it started

Atiende was born during the COVID-19 pandemic, when nobody could go out to shop. Stores needed to keep selling, and their customers needed to keep getting supplies without leaving home.

Together with [Leandro Zacaria](https://www.linkedin.com/in/leandro-zacaria/), we built this project to **bring supermarket shopping closer to the customers** of the businesses that hired the service. We offered it as an app: each business had its own catalog, its own WhatsApp ordering channel and its own dashboard to manage everything coming in.

Over time the project grew from an emergency tool into a complete customer service, sales and after-sales platform for many kinds of businesses.

## What it does

- **Centralizes inquiries and orders.** Every conversation that arrives through WhatsApp is organized in one place; every customer and every order is recorded.
- **24/7 automated service.** The bot guides customers through a simple menu and takes orders, complaints and inquiries without the team needing to be online, on the same WhatsApp number as always.
- **Order and complaint management.** Every order and complaint has a status and can be tracked from the moment it arrives until delivery.
- **Sales team support.** Each customer can be assigned to a salesperson, who receives the confirmed order details to move the sale forward.
- **Task automation.** Customer data capture, status updates and a PDF receipt for every transaction.
- **Web dashboard.** Orders, customers, products, deliveries, reports and a customer map, all at hand.

## Repository structure

| Folder | Contents |
|---|---|
| [`app/`](app/) | The main application: management dashboard, WhatsApp bot (`BotEngine`), end-customer ordering page (`pedidos/`), PDF receipts and reports. |
| [`app-tenants/`](app-tenants/) | Tenant platform (`pedidos_platform`): business onboarding and provisioning, superadmin, plans and subscriptions (MercadoPago and Stripe webhooks). |
| [`app-landing/`](app-landing/) | Public product landing page. |
| `descripcion-atiende.html`, `onboarding-cliente.html` | Sales and customer onboarding material (in Spanish). |

Each business (tenant) has its own isolated database; the tenant platform handles login and defines which modules each one has enabled.

## Stack

- **Backend:** PHP (PHP-FPM) with custom models, AJAX endpoints and server-side views.
- **Database:** MySQL / MariaDB, one database per tenant plus `pedidos_platform`.
- **Frontend:** Bootstrap 5 (Hyper theme), jQuery, DataTables, SweetAlert2, Mapbox GL.
- **Integrations:** WhatsApp Cloud API, MercadoPago, Stripe, PHPMailer.
- **PDF / Excel:** FPDF and PHPExcel.
- **Infrastructure:** Docker Compose (PHP-FPM + Nginx).

## Getting started (development)

Requirements: Docker and Docker Compose. Everything runs inside the containers (PHP, Composer, PHPUnit), not with a local PHP install.

```bash
cd app
cp .env.example .env     # adjust HOST_IP if you are on Linux
./startup.sh             # on Windows: .\startup.ps1
```

`startup.sh` builds the images, starts the containers, initializes the databases and sets permissions.

Day-to-day usage:

```bash
docker compose up -d     # start without resetting the database
docker compose down      # stop
```

`config/database.php` and `config/global.php` are not versioned (they hold secrets). If they go missing, for example after a `git pull`, regenerate them from the templates with:

```bash
./setup-config.sh        # on Windows: .\setup-config.ps1
```

The tenant platform is started the same way from `app-tenants/` (`docker compose up -d`).

## Tests

```bash
# Main app: plain PHP scripts, one per file
docker exec atiende-app php /var/www/atiende/tests/TelefonoNormalizarTest.php

# Tenant platform (PHPUnit)
cd app-tenants && docker compose exec pedidos-app vendor/bin/phpunit
```

## Documentation

The internal docs are written in Spanish.

- [`app/CLAUDE.md`](app/CLAUDE.md): architecture, UI and bot conventions.
- [`app/docs/`](app/docs/): runbooks (migrations, switching the WhatsApp tenant, data deletion), bot menu map and security notes.
- [`app-tenants/docs/`](app-tenants/docs/): tenant platform specs and plans.

## Authors

- **Diego Motta** · [@diegomottadev](https://github.com/diegomottadev)
- **Leandro Zacaria** · [LinkedIn](https://www.linkedin.com/in/leandro-zacaria/)
