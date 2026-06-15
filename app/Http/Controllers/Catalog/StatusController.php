<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\StatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\Status\Infrastructure\Controller\StatusController as InfraStatusController;

#[OA\Tag(name: 'Catalog · Status', description: 'Estados de productos/medicamentos')]
#[OA\Schema(
    schema: 'StatusResponse',
    properties: [
        new OA\Property(property: 'id',          type: 'string', format: 'uuid'),
        new OA\Property(property: 'name',        type: 'string', example: 'Activo'),
        new OA\Property(property: 'code',        type: 'string', example: 'ACTIVO'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'El producto está disponible para venta.'),
        new OA\Property(property: 'is_active',   type: 'boolean', example: true),
        new OA\Property(property: 'created_by',  type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at',  type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string', format: 'date-time'),
    ]
)]
class StatusController extends Controller
{
    public function __construct(
        private readonly InfraStatusController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/statuses',
        summary: 'Listar estados de producto',
        description: 'Lista paginada con filtros opcionales por nombre, código y estado del catálogo.',
        tags: ['Catalog · Status'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search',    in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Búsqueda en nombre o código'),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StatusResponse')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->controller->index([
            'search'    => $request->query('search'),
            'is_active' => $request->query('is_active') !== null
                ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN) : null,
            'per_page'  => $request->query('per_page', 20),
            'page'      => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => StatusResource::collection($result['data']),
            'meta' => [
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/statuses/{id}',
        summary: 'Obtener estado de producto',
        tags: ['Catalog · Status'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estado encontrado', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new StatusResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/statuses',
        summary: 'Crear estado de producto',
        description: 'Permite agregar nuevos estados al catálogo además de los precargados (ACTIVO, DESCONTINUADO, ELIMINADO).',
        tags: ['Catalog · Status'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string', maxLength: 50, example: 'En revisión'),
                    new OA\Property(property: 'code',        type: 'string', maxLength: 20, example: 'EN_REVISION'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Estado creado', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Nombre o código duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:50',
            'code'        => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto = $this->controller->store([...$validated, 'actor_user_id' => $actorUserId]);

        return (new StatusResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/statuses/{id}',
        summary: 'Actualizar estado de producto',
        tags: ['Catalog · Status'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string', maxLength: 50),
                    new OA\Property(property: 'code',        type: 'string', maxLength: 20),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre o código duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:50',
            'code'        => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto = $this->controller->update($id, [...$validated, 'actor_user_id' => $actorUserId]);

        return (new StatusResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/statuses/{id}',
        summary: 'Desactivar estado de producto',
        description: 'Soft delete lógico del catálogo. No elimina el registro.',
        tags: ['Catalog · Status'],
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
