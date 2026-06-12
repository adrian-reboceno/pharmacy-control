<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\RouteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Controller\RouteController as InfraRouteController;

#[OA\Tag(name: 'Catalog · Routes of Administration', description: 'Vías de administración de medicamentos')]
#[OA\Schema(
    schema: 'RouteResponse',
    properties: [
        new OA\Property(property: 'id',          type: 'string', format: 'uuid'),
        new OA\Property(property: 'name',        type: 'string', example: 'Oral'),
        new OA\Property(property: 'code',        type: 'string', example: 'VO'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Administración por la boca.'),
        new OA\Property(property: 'is_active',   type: 'boolean', example: true),
        new OA\Property(property: 'created_by',  type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at',  type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string', format: 'date-time'),
    ]
)]
class RouteController extends Controller
{
    public function __construct(
        private readonly InfraRouteController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/routes-of-administration',
        summary: 'Listar vías de administración',
        description: 'Lista paginada con filtros opcionales por nombre, código y estado.',
        tags: ['Catalog · Routes of Administration'],
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
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RouteResponse')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $isActive = null;
        if ($request->query('is_active') !== null) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $result = $this->controller->index([
            'search'    => $request->query('search'),
            'is_active' => $isActive,
            'per_page'  => $request->query('per_page', 20),
            'page'      => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => RouteResource::collection($result['data']),
            'meta' => [
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/routes-of-administration/{id}',
        summary: 'Obtener vía de administración',
        tags: ['Catalog · Routes of Administration'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vía encontrada', content: new OA\JsonContent(ref: '#/components/schemas/RouteResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new RouteResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/routes-of-administration',
        summary: 'Crear vía de administración',
        tags: ['Catalog · Routes of Administration'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string', maxLength: 100, example: 'Oral'),
                    new OA\Property(property: 'code',        type: 'string', maxLength: 10,  example: 'VO'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Vía creada', content: new OA\JsonContent(ref: '#/components/schemas/RouteResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Nombre o código duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated   = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto         = $this->controller->store([...$validated, 'actor_user_id' => $actorUserId]);

        return (new RouteResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/routes-of-administration/{id}',
        summary: 'Actualizar vía de administración',
        tags: ['Catalog · Routes of Administration'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string', maxLength: 100),
                    new OA\Property(property: 'code',        type: 'string', maxLength: 10),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Vía actualizada', content: new OA\JsonContent(ref: '#/components/schemas/RouteResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre o código duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated   = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto         = $this->controller->update($id, [...$validated, 'actor_user_id' => $actorUserId]);

        return (new RouteResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/routes-of-administration/{id}',
        summary: 'Desactivar vía de administración',
        description: 'Soft delete lógico. No elimina el registro.',
        tags: ['Catalog · Routes of Administration'],
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
