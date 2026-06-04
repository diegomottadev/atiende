# Agregar número de WhatsApp a producción

## 1. Acceso del lado del cliente (Business Manager)

1. Entrar a `business.facebook.com` con la cuenta que es dueña del negocio.
2. Ir a **Configuración del negocio** (Business Settings), el ícono de engranaje.
3. En el menú izquierdo: **Usuarios → Personas**.
4. Tocar **Agregar** (botón azul).
5. Ingresar el correo electrónico (el de tu cuenta de Facebook).
6. Elegir el rol: para desarrollar conviene **Acceso de administrador del negocio**, o al menos **acceso de empleado + asignación a la app puntual**.
7. Enviar la invitación.

### Si la app todavía NO existe

Primero el cliente (o vos ya con acceso de admin a su Business Manager) crea la app **desde dentro del Business Manager del cliente**, no desde tu cuenta personal:

**Business Settings → Cuentas → Aplicaciones → Agregar → Crear nueva app.**

Así nace siendo propiedad del negocio del cliente desde el día uno.

Referencia del panel de usuarios:

`https://business.facebook.com/latest/settings/business_users?business_id=458405511287182&selected_user_id=2466806890447024&passkey_ref=false`

## 2. Usuario de sistema (token permanente)

Agregar un **usuario de sistema** para tener un token permanente y asignarle las apps y las cuentas de WhatsApp.

**Ruta:** Información del negocio → Usuarios → Usuarios de sistema

**Requisitos:**

- Un **número de teléfono registrado para WhatsApp** que **NO** esté usado en la app normal de WhatsApp ni en WhatsApp Business del celular.
- Generar el token.
- Asignar al usuario de sistema las apps y las cuentas de WhatsApp correspondientes.

## 3. Configuración en la plataforma de tenants

Cargar los siguientes datos:

- **WABA ID**
- **Identificador de número de teléfono** (Phone Number ID)
- **App Secret** (clave secreta de la app Meta): se obtiene en la configuración de la app → sección **Básico → App Secret**.
- **Token permanente** (el del usuario de sistema)

## 4. Políticas de privacidad

Agregar la **URL de las políticas de privacidad** en la configuración de la app de Facebook.

## 5. Configuración del webhook

- Configurar el **webhook** de la aplicación.
- Cargar el **secret/API del webhook** de la aplicación web.
