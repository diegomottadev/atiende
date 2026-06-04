# Teclazo — Template Packs

Curated professional template packs for the Teclazo snippet expander (Chrome MV3 extension). Each pack is a JSON array of template objects.

## JSON shape

```json
[
  {
    "trigger": "/honorarios",
    "content": "Hola {cliente}:\n\nMis honorarios son...",
    "variables": ["cliente"],
    "category": "legal"
  }
]
```

| Field | Description |
|---|---|
| `trigger` | Short slash-command the user types (e.g. `/cotizar`). Lowercase, no spaces. |
| `content` | The expanded text. Use `\n` for line breaks. WhatsApp markdown (`*bold*`) is supported. |
| `variables` | Exact list of `{token}` names present in `content`, derived by the regex `/\{([a-zA-Z0-9_]+)\}/`. Deduplicated, in order of appearance. |
| `category` | Pack identifier string. |

## Auto-filled variables

The Teclazo engine automatically fills `{fecha}` (today's date) and `{hora}` (current time) at expansion time. You may use them in any template — they appear in the `variables` array but the user is never prompted for them.

All other `{variable}` tokens prompt the user for a value at expansion time.

## Language

All templates are written in **es-AR** (Spanish, Argentina). Voseo is used where natural. Monetary amounts use the peso sign `$`. Dates follow the Argentine format (dd/mm/aaaa).

## Packs included

| File | Rubro | Templates |
|---|---|---|
| `legal.json` | Abogados / Estudios jurídicos | 20 |
| `inmobiliaria.json` | Inmobiliarias / Asesores inmobiliarios | 12 |
| `ecommerce.json` | Tiendas online / E-commerce | 12 |
| `salud.json` | Profesionales de la salud / Consultorios | 12 |

## Aviso importante — templates de LEGAL y SALUD

Los templates de los packs `legal.json` y `salud.json` son **puntos de partida** que cada profesional debe revisar, adaptar y personalizar antes de usar. Ningún template de este pack constituye asesoramiento legal ni médico. Las comunicaciones legalmente relevantes (intimaciones, notificaciones fehacientes, indicaciones médicas, recetas) deben ser redactadas y supervisadas por el profesional habilitado conforme a la normativa vigente y al caso concreto. El uso de estos templates es responsabilidad exclusiva del profesional que los emplea.
