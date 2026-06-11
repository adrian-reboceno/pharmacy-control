<?php

// ── ARCHIVO: app/Http/Controllers/Catalog/CategoryController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\CategoryResource;
use App\Http\Resources\Catalog\CategoryTreeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\Categories\Infrastructure\Controller\CategoryController as InfraCategoryController;

#[OA\Tag(name: 'Catalog · Categories', description: 'Categorías jerárquicas de productos (árbol libre con Adjacency List)')]

#[OA\Schema(
    schema: 'CategoryResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'Analgésicos'),
        new OA\Property(property: 'slug', type: 'string', example: 'analgesicos'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_root', type: 'boolean', example: false),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]

#[OA\Schema(
    schema: 'CategoryTreeResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/CategoryResponse'),
        new OA\Schema(properties: [
            new OA\Property(
                property: 'children',
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/CategoryTreeResponse')
            ),
        ]),
    ]
)]

class CategoryController extends Controller
{
    public function __construct(
        private readonly InfraCategoryController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/categories/tree',
        summary: 'Árbol completo de categorías',
        description: 'Devuelve todas las categorías raíz con hijos anidados recursivamente.',
        tags: ['Catalog · Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'Filtrar nodos por estado'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Árbol de categorías',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryTreeResponse')),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function tree(Request $request): JsonResponse
    {
        $isActive = null;
        if ($request->query('is_active') !== null) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $roots = $this->controller->tree(['is_active' => $isActive]);

        return response()->json([
            'data' => CategoryTreeResource::collection($roots),
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/categories/{id}',
        summary: 'Obtener categoría',
        description: 'Devuelve el nodo sin hijos anidados.',
        tags: ['Catalog · Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categoría encontrada', content: new OA\JsonContent(ref: '#/components/schemas/CategoryResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new CategoryResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/categories',
        summary: 'Crear categoría',
        description: 'Crea una categoría. Si parent_id es null se crea como raíz. Si slug no se envía, se auto-genera desde el nombre.',
        tags: ['Catalog · Categories'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Analgésicos'),
                    new OA\Property(property: 'slug', type: 'string', nullable: true, pattern: '^[a-z0-9]+(-[a-z0-9]+)*$', example: 'analgesicos'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Categoría creada', content: new OA\JsonContent(ref: '#/components/schemas/CategoryResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, description: 'parent_id no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Slug duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|uuid',
            'name' => 'required|string|max:120',
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => 'nullable|string|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->store([
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new CategoryResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/categories/{id}',
        summary: 'Actualizar categoría',
        description: 'Actualiza nombre, slug, descripción y/o padre. No permite ciclos en el árbol ni auto-referencia.',
        tags: ['Catalog · Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'name', type: 'string', maxLength: 120),
                    new OA\Property(property: 'slug', type: 'string', nullable: true, pattern: '^[a-z0-9]+(-[a-z0-9]+)*$'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Categoría actualizada', content: new OA\JsonContent(ref: '#/components/schemas/CategoryResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Slug duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(
                response: 422,
                description: 'Ciclo detectado o validación fallida',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No se puede asignar como padre porque crearía un ciclo.'),
                        new OA\Property(property: 'error', type: 'string', example: 'CYCLE_DETECTED'),
                    ]
                )
            ),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|uuid',
            'name' => 'required|string|max:120',
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => 'nullable|string|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->update($id, [
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new CategoryResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/categories/{id}',
        summary: 'Desactivar categoría',
        description: 'Soft delete lógico. Los hijos NO se desactivan automáticamente.',
        tags: ['Catalog · Categories'],
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
