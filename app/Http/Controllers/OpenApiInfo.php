<?php
// ── ARCHIVO: app/Http/Controllers/OpenApiInfo.php ──

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'PharmaControl API',
    description: 'ERP para farmacias multi-sucursal en México con regulación COFEPRIS.
Autenticación JWT propio (HS256). Todos los endpoints protegidos requieren Authorization: Bearer <token>.',
)]
#[OA\Server(url: 'http://localhost:8080/api/v1/', description: 'Local Docker')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]

// ── Schemas reutilizables ──────────────────────────────────────

#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'total',        type: 'integer', example: 20),
        new OA\Property(property: 'per_page',     type: 'integer', example: 20),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page',    type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Recurso no encontrado.'),
        new OA\Property(property: 'error',   type: 'string', example: 'NOT_FOUND', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The name field is required.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ]
)]

// ── Responses reutilizables ───────────────────────────────────

#[OA\Response(
    response: 'Unauthorized',
    description: 'Token ausente, inválido o expirado',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse', example: ['message' => 'Unauthenticated.'])
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'Sin el permiso requerido',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Recurso no encontrado',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
#[OA\Response(
    response: 'UnprocessableEntity',
    description: 'Error de validación',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
)]
#[OA\Response(response: 'NoContent', description: 'Operación exitosa sin contenido')]

class OpenApiInfo {}