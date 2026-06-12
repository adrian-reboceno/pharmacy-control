<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\UnitResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Controller\UnitController as InfraUnitController;

#[OA\Tag(name: 'Catalog · Units', description: 'Unidades de medida farmacéuticas')]
#[OA\Schema(
    schema: 'UnitResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Miligramo'),
        new OA\Property(property: 'symbol', type: 'string', example: 'mg'),
        new OA\Property(property: 'type', type: 'string', enum: ['QUANTITY', 'CONCENTRATION'], example: 'CONCENTRATION'),
        new OA\Property(property: 'type_label', type: 'string', example: 'Concentración / Dosis'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class UnitOfMeasurementController extends Controller
{
    public function __construct(
        private readonly InfraUnitController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/units',
        summary: 'Listar unidades de medida',
        tags: ['Catalog · Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['QUANTITY', 'CONCENTRATION']), description: 'Filtrar por tipo'),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Búsqueda en nombre o símbolo'),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de unidades',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UnitResponse')),
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
            'type' => $request->query('type'),
            'search' => $request->query('search'),
            'is_active' => $isActive,
            'per_page' => $request->query('per_page', 20),
            'page' => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => UnitResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/units/{id}',
        summary: 'Obtener unidad de medida',
        tags: ['Catalog · Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unidad encontrada', content: new OA\JsonContent(ref: '#/components/schemas/UnitResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new UnitResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/units',
        summary: 'Crear unidad de medida',
        tags: ['Catalog · Units'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'symbol', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 80, example: 'Miligramo'),
                    new OA\Property(property: 'symbol', type: 'string', maxLength: 20, example: 'mg'),
                    new OA\Property(property: 'type', type: 'string', enum: ['QUANTITY', 'CONCENTRATION'], example: 'CONCENTRATION'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Unidad creada', content: new OA\JsonContent(ref: '#/components/schemas/UnitResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Nombre o símbolo duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'symbol' => 'required|string|max:20',
            'type' => 'required|string|in:QUANTITY,CONCENTRATION',
        ]);
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto = $this->controller->store([...$validated, 'actor_user_id' => $actorUserId]);

        return (new UnitResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/units/{id}',
        summary: 'Actualizar unidad de medida',
        tags: ['Catalog · Units'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'symbol', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 80, example: 'Miligramo'),
                    new OA\Property(property: 'symbol', type: 'string', maxLength: 20, example: 'mg'),
                    new OA\Property(property: 'type', type: 'string', enum: ['QUANTITY', 'CONCENTRATION']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Unidad actualizada', content: new OA\JsonContent(ref: '#/components/schemas/UnitResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre o símbolo duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'symbol' => 'required|string|max:20',
            'type' => 'required|string|in:QUANTITY,CONCENTRATION',
        ]);
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto = $this->controller->update($id, [...$validated, 'actor_user_id' => $actorUserId]);

        return (new UnitResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/units/{id}',
        summary: 'Desactivar unidad de medida',
        description: 'Soft delete lógico. No elimina el registro.',
        tags: ['Catalog · Units'],
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
