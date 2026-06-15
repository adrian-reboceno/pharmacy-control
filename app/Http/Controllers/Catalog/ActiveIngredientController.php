<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\IngredientResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Controller\IngredientController as InfraIngredientController;

#[OA\Tag(name: 'Catalog · Active Ingredients', description: 'Ingredientes activos farmacológicos (DCI/INN)')]
#[OA\Schema(
    schema: 'IngredientResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Amoxicilina'),
        new OA\Property(property: 'dci_code', type: 'string', example: 'amoxicillin'),
        new OA\Property(property: 'cas_number', type: 'string', nullable: true, example: '26787-78-0'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ActiveIngredientController extends Controller
{
    public function __construct(
        private readonly InfraIngredientController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/active-ingredients',
        summary: 'Listar ingredientes activos',
        description: 'Lista paginada. La búsqueda aplica sobre nombre, código DCI y número CAS.',
        tags: ['Catalog · Active Ingredients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Búsqueda en nombre, DCI o CAS'),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/IngredientResponse')),
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
            'search' => $request->query('search'),
            'is_active' => $request->query('is_active') !== null
                ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN) : null,
            'per_page' => $request->query('per_page', 20),
            'page' => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => IngredientResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/active-ingredients/{id}',
        summary: 'Obtener ingrediente activo',
        tags: ['Catalog · Active Ingredients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ingrediente encontrado', content: new OA\JsonContent(ref: '#/components/schemas/IngredientResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new IngredientResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/active-ingredients',
        summary: 'Crear ingrediente activo',
        description: 'El número CAS es opcional. Si se envía debe tener el formato NNNNNN-NN-N (ej: 26787-78-0).',
        tags: ['Catalog · Active Ingredients'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'dci_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Amoxicilina'),
                    new OA\Property(property: 'dci_code', type: 'string', maxLength: 30, example: 'amoxicillin'),
                    new OA\Property(property: 'cas_number', type: 'string', nullable: true, example: '26787-78-0'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ingrediente creado', content: new OA\JsonContent(ref: '#/components/schemas/IngredientResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Nombre, DCI o CAS duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'dci_code' => 'required|string|max:30',
            'cas_number' => ['nullable', 'string', 'regex:/^\d{2,7}-\d{2}-\d$/'],
            'description' => 'nullable|string|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto = $this->controller->store([...$validated, 'actor_user_id' => $actorUserId]);

        return (new IngredientResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/active-ingredients/{id}',
        summary: 'Actualizar ingrediente activo',
        tags: ['Catalog · Active Ingredients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'dci_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150),
                    new OA\Property(property: 'dci_code', type: 'string', maxLength: 30),
                    new OA\Property(property: 'cas_number', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ingrediente actualizado', content: new OA\JsonContent(ref: '#/components/schemas/IngredientResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre, DCI o CAS duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'dci_code' => 'required|string|max:30',
            'cas_number' => ['nullable', 'string', 'regex:/^\d{2,7}-\d{2}-\d$/'],
            'description' => 'nullable|string|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto = $this->controller->update($id, [...$validated, 'actor_user_id' => $actorUserId]);

        return (new IngredientResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/active-ingredients/{id}',
        summary: 'Desactivar ingrediente activo',
        description: 'Soft delete lógico. No elimina el registro.',
        tags: ['Catalog · Active Ingredients'],
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
