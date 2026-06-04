# Prompt: Análisis de Gaps del Equipo de Agentes — Atiende

Analizá el equipo de agentes actual del proyecto Atiende y determiná
qué roles o flujos están faltando para tener un ciclo de desarrollo
completo y profesional.

---

## Equipo actual (agentes ya definidos)

| Agente | Archivo | Responsabilidad |
|---|---|---|
| Product Strategist (CPO) | `product-strategist.md` | Nichos, roadmap de mercado, pitch de venta |
| Product Owner | `product-owner.md` | User stories, backlog, priorización RICE |
| Analista Funcional | `functional-analyst.md` | Specs técnicas, wireframes, endpoints, SQL |
| Fullstack Developer | `fullstack-developer.md` | Implementación PHP + JS + DB |
| Security Auditor | `security-audit.md` | Detección y corrección de vulnerabilidades |

---

## Tu tarea

Evaluá el ciclo de vida completo de un producto SaaS como Atiende y
respondé con precisión:

### 1. GAPS DE ROL
¿Qué roles profesionales faltan en el equipo para cubrir el ciclo
completo? Para cada uno:
- **Nombre del rol**
- **Por qué es necesario** (qué no está haciendo nadie hoy)
- **Impacto de no tenerlo** (qué falla o queda sin cubrir)
- **Prioridad**: Crítico / Importante / Recomendable
- **¿Debería ser un agente propio?** Sí / No (justificá)

### 2. GAPS DE FLUJO
¿Qué etapas del proceso de desarrollo no tienen cobertura?
Considerá al menos:
- Discovery y validación de hipótesis
- Diseño UI/UX
- Testing y QA
- DevOps / CI/CD / Deploy
- Monitoreo y observabilidad
- Customer Success / Soporte
- Documentación técnica
- Legal / Compliance (datos personales, GDPR, PCI)
- Growth / Marketing / SEO técnico

### 3. GAPS DE COMUNICACIÓN
¿Hay momentos del flujo donde dos agentes existentes necesitan
comunicarse pero no tienen un protocolo definido?
Ejemplos: ¿Quién cierra el loop cuando el dev termina? ¿Quién
valida que la spec del analista no contradice la estrategia del CPO?

### 4. PROPUESTA CONCRETA
Para los 3 gaps más críticos:
- Nombre del agente o proceso faltante
- Qué prompt necesitaría (título + 3 responsabilidades clave)
- Cómo se integra con el equipo actual (a quién alimenta, de quién
  recibe input)

### 5. MAPA DEL EQUIPO COMPLETO
Al final, dibujá el flujo completo del equipo — actual + propuesto —
mostrando quién le pasa trabajo a quién:

```
[Agente A] → [Agente B] → [Agente C]
                ↑               ↓
           [Agente D] ←──────────
```

---

## Contexto adicional del proyecto

- Es un SaaS en crecimiento, no una app corporativa grande
- El equipo de desarrollo es pequeño (1-3 devs)
- Está en expansión a múltiples nichos en LATAM
- Tiene deuda técnica de seguridad conocida (SQL injection, XSS, CSRF)
- El stack es PHP legacy + módulos modernos coexistiendo
- Tiene una app satélite (`pedidos-platform`) en desarrollo
- Los clientes son empresas (B2B), no usuarios individuales
- No tiene QA dedicado ni CI/CD formal todavía

Sé pragmático — proponé agentes que tengan valor real para este
tamaño de equipo, no una burocracia de 15 roles para una startup.
