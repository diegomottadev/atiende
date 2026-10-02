# Atiende

> Tu WhatsApp atiende, toma pedidos y organiza tu negocio.

**Atiende** es una plataforma multi-tenant que convierte el WhatsApp de un negocio en un canal de atención y ventas automatizado: un bot conversa con los clientes, toma pedidos, reclamos y consultas, y todo queda registrado en un panel web de gestión.

## Cómo nació

Atiende surgió durante la pandemia de COVID-19, cuando nadie podía salir a hacer las compras. Los comercios necesitaban seguir vendiendo y sus clientes necesitaban seguir abasteciéndose sin moverse de su casa.

Junto con [Leandro Zacaria](https://www.linkedin.com/in/leandro-zacaria/) creamos este proyecto para **acercar las compras del supermercado a los clientes** de los negocios que contrataban el servicio. Lo ofrecíamos como una app: cada negocio tenía su propio catálogo, su canal de pedidos por WhatsApp y su panel para gestionar todo lo que entraba.

Con el tiempo el proyecto creció de una herramienta de emergencia a una plataforma completa de atención, ventas y postventa para distintos rubros.

## Qué hace

- **Centraliza consultas y pedidos.** Todas las conversaciones que llegan por WhatsApp se ordenan en un único lugar; cada cliente y cada pedido queda registrado.
- **Atención automática 24/7.** El bot guía al cliente por un menú simple y toma pedidos, reclamos y consultas sin que el equipo tenga que estar conectado, en el mismo número de WhatsApp de siempre.
- **Gestión de pedidos y reclamos.** Cada pedido y reclamo tiene su estado y se puede seguir desde que entra hasta la entrega.
- **Asistencia al equipo de ventas.** Cada cliente puede asignarse a un vendedor, que recibe la información del pedido confirmado para avanzar con la venta.
- **Automatización de tareas.** Registro de datos del cliente, actualización de estados y comprobante en PDF de cada operación.
- **Panel web.** Pedidos, clientes, artículos, repartos, reportes y un mapa de clientes, todo a mano.

## Estructura del repositorio

| Carpeta | Qué contiene |
|---|---|
| [`app/`](app/) | La aplicación principal: panel de gestión, bot de WhatsApp (`BotEngine`), página de pedidos para el cliente final (`pedidos/`), tickets PDF y reportes. |
| [`app-tenants/`](app-tenants/) | Plataforma de tenants (`pedidos_platform`): alta y aprovisionamiento de negocios, superadmin, planes y suscripciones (webhooks de MercadoPago y Stripe). |
| [`app-landing/`](app-landing/) | Landing pública del producto. |
| `descripcion-atiende.html`, `onboarding-cliente.html` | Material comercial y de onboarding para clientes. |

Cada negocio (tenant) tiene su propia base de datos aislada; la plataforma de tenants autentica el login y define qué módulos tiene habilitados cada uno.

## Stack

- **Backend:** PHP (PHP-FPM) con modelos propios, endpoints AJAX y vistas server-side.
- **Base de datos:** MySQL / MariaDB, una base por tenant + `pedidos_platform`.
- **Frontend:** Bootstrap 5 (tema Hyper), jQuery, DataTables, SweetAlert2, Mapbox GL.
- **Integraciones:** WhatsApp Cloud API, MercadoPago, Stripe, PHPMailer.
- **PDF / Excel:** FPDF y PHPExcel.
- **Infraestructura:** Docker Compose (PHP-FPM + Nginx).

## Puesta en marcha (desarrollo)

Requisitos: Docker y Docker Compose. Todo se corre dentro de los containers (PHP, Composer, PHPUnit), no con un PHP local.

```bash
cd app
cp .env.example .env     # ajustar HOST_IP si estás en Linux
./startup.sh             # en Windows: .\startup.ps1
```

`startup.sh` construye las imágenes, levanta los containers, inicializa las bases de datos y aplica permisos.

Uso diario:

```bash
docker compose up -d     # levantar sin resetear la base
docker compose down      # apagar
```

Los archivos `config/database.php` y `config/global.php` no se versionan (contienen secretos). Si faltan —por ejemplo después de un `git pull`— se regeneran a partir de los templates con:

```bash
./setup-config.sh        # en Windows: .\setup-config.ps1
```

La plataforma de tenants se levanta de la misma forma desde `app-tenants/` (`docker compose up -d`).

## Tests

```bash
# App principal: scripts PHP planos, uno por archivo
docker exec atiende-app php /var/www/atiende/tests/TelefonoNormalizarTest.php

# Plataforma de tenants (PHPUnit)
cd app-tenants && docker compose exec pedidos-app vendor/bin/phpunit
```

## Documentación

- [`app/CLAUDE.md`](app/CLAUDE.md): arquitectura, convenciones de UI y del bot.
- [`app/docs/`](app/docs/): runbooks (migraciones, cambio de tenant de WhatsApp, eliminación de datos), mapa del menú del bot y notas de seguridad.
- [`app-tenants/docs/`](app-tenants/docs/): specs y planes de la plataforma de tenants.

## Autores

- **Diego Motta** · [@diegomottadev](https://github.com/diegomottadev)
- **Leandro Zacaria** · [LinkedIn](https://www.linkedin.com/in/leandro-zacaria/)
