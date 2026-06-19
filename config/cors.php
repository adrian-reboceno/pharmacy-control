<?php

// ── ARCHIVO: config/cors.php ──
// Configuración CORS para soportar cookies HttpOnly con withCredentials: true
// desde el frontend Angular en desarrollo y producción.

return [

    // Rutas de la API que aceptan requests cross-origin
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Métodos permitidos
    'allowed_methods' => ['*'],

    // ── CRÍTICO para cookies HttpOnly ────────────────────────────────────────
    // allowed_origins NO puede ser ['*'] cuando supports_credentials es true.
    // El browser rechaza la combinación Access-Control-Allow-Origin: * +
    // Access-Control-Allow-Credentials: true. Se deben listar los orígenes
    // exactos del frontend Angular.
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:4200')),

    // No usar patrones de origen con credentials
    'allowed_origins_patterns' => [],

    // Headers que el frontend puede enviar
    'allowed_headers' => ['*'],

    // ── CRÍTICO para cookies HttpOnly ────────────────────────────────────────
    // Habilita Access-Control-Allow-Credentials: true en el response.
    // Sin esto, el browser descarta las cookies aunque el servidor las envíe.
    'supports_credentials' => true,

    // Headers que el frontend puede leer del response
    'exposed_headers' => [],

    // Tiempo de caché para el preflight OPTIONS (segundos)
    'max_age' => 0,
];