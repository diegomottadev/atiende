// Base de la API REST (backend Laravel + Sanctum).
// Dev local: http://localhost:8000  ·  Prod: https://api.clubpedidos.com
const API_BASE_URL = 'http://localhost:8000';

// Limite de plantillas del plan gratuito. En modo logueado lo aplica el backend
// (middleware EnforceTemplateLimit); en modo local lo simula api.js.
const FREE_PLAN_TEMPLATE_LIMIT = 5;
