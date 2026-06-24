<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\LocationResource;
use App\Http\Resources\Catalog\LocationTreeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\Location\Infrastructure\Controller\LocationController as InfraLocationController;

#[OA\Tag(name: 'Catalog · Locations', description: 'Ubicaciones físicas de productos en la farmacia (Zona > Pasillo > Estante > Posición)')]

#[OA\Schema(
    schema: 'LocationResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Pos 1'),
        new OA\Property(property: 'level', type: 'integer', example: 4, description: '1=Zona, 2=Pasillo, 3=Estante, 4=Posición'),
        new OA\Property(property: 'level_label', type: 'string', example: 'Posición'),
        new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_leaf', type: 'boolean', example: true),
        new OA\Property(property: 'full_path', type: 'string', example: 'OTC General > Pasillo 1 > Estante 1 > Pos 1'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]

class LocationController extends Controller
{
    public function __construct(
        private readonly InfraLocationController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/locations',
        summary: 'Lista plana de ubicaciones',
        tags: ['Catalog · Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'level', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [1, 2, 3, 4])),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de ubicaciones',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationResponse')),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $locations = $this->controller->index($request->query());

        return response()->json([
            'data' => LocationResource::collection($locations),
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/locations/tree',
        summary: 'Árbol completo de ubicaciones',
        tags: ['Catalog · Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Árbol anidado Zona > Pasillo > Estante > Posición',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationResponse')),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function tree(Request $request): JsonResponse
    {
        $roots = $this->controller->tree($request->query());

        return response()->json([
            'data' => LocationTreeResource::collection($roots),
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/locations/leaves',
        summary: 'Posiciones activas (level=4)',
        description: 'Devuelve solo las Posiciones activas. Se usa en el formulario de creación de productos.',
        tags: ['Catalog · Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de posiciones',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationResponse')),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function leaves(Request $request): JsonResponse
    {
        $leaves = $this->controller->leaves($request->query());

        return response()->json([
            'data' => LocationResource::collection($leaves),
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/locations/{id}',
        summary: 'Obtener ubicación por ID',
        tags: ['Catalog · Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ubicación encontrada', content: new OA\JsonContent(ref: '#/components/schemas/LocationResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new LocationResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/locations',
        summary: 'Crear ubicación',
        description: 'Crea un nodo en la jerarquía. El nivel debe coincidir con el nivel hijo del padre dado. Las Zonas (level=1) deben tener parent_id nulo.',
        tags: ['Catalog · Locations'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'level'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Pasillo A'),
                    new OA\Property(property: 'level', type: 'integer', enum: [1, 2, 3, 4], example: 2),
                    new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ubicación creada', content: new OA\JsonContent(ref: '#/components/schemas/LocationResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, description: 'parent_id no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Nombre duplicado en el mismo nivel', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Nivel inválido para el padre dado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'level' => 'required|integer|in:1,2,3,4',
            'parent_id' => 'nullable|uuid',
            'description' => 'nullable|string|max:255',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->store([
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new LocationResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/locations/{id}',
        summary: 'Actualizar ubicación',
        description: 'Actualiza nombre y descripción. El parent_id y el level son inmutables tras la creación.',
        tags: ['Catalog · Locations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ubicación actualizada', content: new OA\JsonContent(ref: '#/components/schemas/LocationResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->update($id, [
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new LocationResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/locations/{id}',
        summary: 'Desactivar ubicación',
        description: 'Soft delete lógico (is_active = false). Los hijos NO se desactivan automáticamente.',
        tags: ['Catalog · Locations'],
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
