# Runbook [DEV] — Probar otro tenant con el mismo número de WhatsApp

> Solo para **desarrollo**. Permite usar el mismo número de prueba de WhatsApp para hablar con el bot de distintos tenants, sin configurar números nuevos en Meta.

## Cómo usarlo

El webhook (`ws/webhook.php`) rutea por `whatsapp_phone_id`. El script `switch_wa_tenant.php` mueve ese número al tenant que quieras probar.

**Apuntar el número a un tenant (ej. `demo`):**
```bash
docker exec -it demo_atiende_app php /var/www/atiende/switch_wa_tenant.php demo
```
Después, **mandá un WhatsApp al número de prueba → ahora hablás con el bot de `demo`**.

**Volver a `corp`:**
```bash
docker exec -it demo_atiende_app php /var/www/atiende/switch_wa_tenant.php corp
```

**Cualquier otro tenant:**
```bash
docker exec -it demo_atiende_app php /var/www/atiende/switch_wa_tenant.php <slug>
```

## Qué hace

Mueve el `whatsapp_phone_id` + `token` + `app_secret` (las credenciales del **mismo número físico**) al tenant destino y lo libera de los demás, para que el webhook (que rutea por `phone_id`) lo mande a la empresa que elegiste. **No se envía nada a Meta** — es solo el ruteo interno en `pedidos_platform.tenants`.

## Notas

- Es un **switch exclusivo**: el número apunta a **un** tenant a la vez (cuando está en `demo`, `corp` no recibe; volvés con el comando).
- Funciona porque es el mismo número/credenciales; el bot de cada tenant usa su propia DB (`wb_<slug>`) y su propio menú.
- Es **solo para dev**. Para producción real (varias empresas en simultáneo sobre un mismo número) iría la **Opción B** (deep-link `wa.me/<num>?text=<slug>` + `contactos.tenant_slug` + ruteo en el webhook). Ver más abajo.
- **Borrá `switch_wa_tenant.php`** cuando termines de probar (es un helper de desarrollo).

## Producción (Opción B, no implementada) — para referencia

WhatsApp no permite nativamente varias empresas en un número (Meta entrega por `phone_number_id` = una identidad). Para soportarlo en serio habría que:
1. Apuntar el `phone_id` a un tenant "router".
2. Leer el primer mensaje / deep-link (`wa.me/<num>?text=corp`) o el `referral` de Meta para elegir el tenant real.
3. Guardar la empresa elegida por contacto (`contactos.tenant_slug`) para rutear los mensajes siguientes.

Trade-offs: marca/nombre del remitente compartido, ventana de 24h y métricas de calidad compartidas, aislamiento lógico (no por número). Para empresas independientes lo recomendado sigue siendo **un número por empresa**.
