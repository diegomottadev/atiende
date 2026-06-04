---
name: php-standards
description: Use when writing any new PHP file, model, handler or helper in this project. Apply naming, structure, typing and output rules specific to the Atiende stack.
---

# Estándares PHP — Atiende

## Naming

- Clases: `PascalCase` → `RepartoService`, `WhatsAppClient`
- Métodos y variables: `camelCase` → `getWebMasterConfig()`, `$tenantSlug`
- Constantes: `UPPER_SNAKE_CASE` → `PLATFORM_ENCRYPTION_KEY`
- Archivos: mismo nombre que la clase → `Reparto.php` para clase `Reparto`

## Estructura de archivos

- Un modelo = un archivo en `modelos/` — lógica de negocio pura
- Un endpoint = un archivo en `ajax/` — solo recibe input, llama modelo, devuelve JSON
- Helpers globales van en `config/global.php` (funciones sin estado como `tenantUrl()`)
- Nunca mezclar HTML con lógica de negocio en archivos de `modelos/`

## Tipado y calidad

- Usar type hints en parámetros y retornos en código nuevo:
```php
public function enviar(string $telefono, string $texto): array
```
- Nunca suprimir errores con `@` — capturar con try/catch y loguear
- Nunca usar `exit` o `die` en producción — retornar array de error o lanzar excepción
- Preferir `match` sobre `switch` en PHP 8+
- Usar `??` y `?->` para null-safety en lugar de isset encadenados

## Output JSON (ajax/)

```php
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
exit;
```
- Siempre `JSON_UNESCAPED_UNICODE` para no escapar caracteres españoles
- Estructura estándar: `['ok' => bool, 'data' => mixed]` o `['ok' => false, 'error' => string]`

## Separación de responsabilidades

- `ajax/` handlers: validar input → llamar modelo → devolver JSON; sin lógica de negocio
- `modelos/` classes: toda la lógica; sin `$_GET`/`$_POST`/`$_SESSION` directos
- `vistas/` templates: solo HTML + datos ya preparados; sin queries SQL

## Comentarios

- Solo cuando el WHY no es obvio (constraint oculta, workaround de bug específico)
- Nunca comentar el WHAT — el código bien nombrado ya lo dice
- No docblocks multi-línea para métodos obvios

## Duplicación

- Si el mismo bloque aparece en 2+ archivos → extraer a método en el modelo o helper en `config/`
- Referencia: `getWebMasterConfig()` y `tenantUrl()` en `config/global.php` son ejemplos de este patrón
