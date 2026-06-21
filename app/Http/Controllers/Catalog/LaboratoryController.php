<?php

// ── ARCHIVO: app/Http/Controllers/Catalog/LaboratoryController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\LaboratoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\Laboratories\Infrastructure\Controller\LaboratoryController as InfraLaboratoryController;

#[OA\Tag(name: 'Catalog · Laboratories', description: 'Catálogo de laboratorios fabricantes')]

#[OA\Schema(
    schema: 'LaboratoryResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Pfizer'),
        new OA\Property(property: 'country_code', type: 'string', example: 'US'),
        new OA\Property(property: 'website', type: 'string', nullable: true, example: 'https://www.pfizer.com'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]

class LaboratoryController extends Controller
{
    public function __construct(
        private readonly InfraLaboratoryController $controller,
    ) {}

    #[Route('GET', '/api/v1/catalog/laboratories',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    #[OA\Get(
        path: '/v1/catalog/laboratories',
        summary: 'Listar laboratorios',
        description: 'Lista paginada con filtros opcionales por nombre, país y estado.',
        tags: ['Catalog · Laboratories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Búsqueda parcial por nombre'),
            new OA\Parameter(name: 'country_code', in: 'query', required: false, schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 2), description: 'Código ISO 3166-1 alpha-2'),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de laboratorios',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LaboratoryResponse')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->controller->index($request->query());

        return response()->json([
            'data' => LaboratoryResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }

    #[Route('GET', '/api/v1/catalog/laboratories/{id}',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    #[OA\Get(
        path: '/v1/catalog/laboratories/{id}',
        summary: 'Obtener laboratorio',
        tags: ['Catalog · Laboratories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Laboratorio encontrado', content: new OA\JsonContent(ref: '#/components/schemas/LaboratoryResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new LaboratoryResource($dto))->response()->setStatusCode(200);
    }

    #[Route('POST', '/api/v1/catalog/laboratories',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]

    #[OA\Post(
        path: '/v1/catalog/laboratories',
        summary: 'Crear laboratorio',
        tags: ['Catalog · Laboratories'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'country_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Pfizer'),
                    new OA\Property(property: 'country_code', type: 'string', minLength: 2, maxLength: 2, example: 'US'),
                    new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true, example: 'https://www.pfizer.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Laboratorio creado', content: new OA\JsonContent(ref: '#/components/schemas/LaboratoryResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Nombre duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'country_code' => 'required|string|size:2',
            'website' => 'nullable|url|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->store([
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new LaboratoryResource($dto))->response()->setStatusCode(201);
    }

    #[Route('PUT', '/api/v1/catalog/laboratories/{id}',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    #[OA\Put(
        path: '/v1/catalog/laboratories/{id}',
        summary: 'Actualizar laboratorio',
        tags: ['Catalog · Laboratories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'country_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Pfizer Inc.'),
                    new OA\Property(property: 'country_code', type: 'string', minLength: 2, maxLength: 2, example: 'US'),
                    new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Laboratorio actualizado', content: new OA\JsonContent(ref: '#/components/schemas/LaboratoryResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'country_code' => 'required|string|size:2',
            'website' => 'nullable|url|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->update($id, [
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new LaboratoryResource($dto))->response()->setStatusCode(200);
    }

    #[Route('DELETE', '/api/v1/catalog/laboratories/{id}',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]

    #[OA\Delete(
        path: '/v1/catalog/laboratories/{id}',
        summary: 'Desactivar laboratorio',
        description: 'Soft delete lógico — establece is_active = false. No elimina el registro.',
        tags: ['Catalog · Laboratories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $this->controller->destroy($id, $actorUserId);

        return response()->json(null, 204);
    }
}
