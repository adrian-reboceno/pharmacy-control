<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\Products\Infrastructure\Controller\ProductController as InfraProductController;

#[OA\Tag(name: 'Catalog · Products', description: 'Productos farmacéuticos (genéricos y de marca)')]
#[OA\Schema(
    schema: 'ProductResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', type: 'string', enum: ['GENERIC', 'BRANDED']),
        new OA\Property(property: 'type_label', type: 'string'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'status_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'laboratory_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'sale_condition', type: 'string', enum: ['SIN_RECETA', 'CON_RECETA', 'CON_RECETA_RETENIDA']),
        new OA\Property(property: 'sale_condition_label', type: 'string'),
        new OA\Property(property: 'sanitary_reg', type: 'string', nullable: true),
        new OA\Property(property: 'barcode', type: 'string', nullable: true),
        new OA\Property(property: 'specs', type: 'object'),
        new OA\Property(property: 'stock_config', type: 'object'),
        new OA\Property(property: 'margins', type: 'object'),
        new OA\Property(property: 'ingredients', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'image_urls', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ProductController extends Controller
{
    public function __construct(
        private readonly InfraProductController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/products',
        summary: 'Listar productos',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['GENERIC', 'BRANDED'])),
            new OA\Parameter(name: 'status_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'laboratory_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'sale_condition', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'manage_lots', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de productos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductResponse')),
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
            'search'        => $request->query('search'),
            'type'          => $request->query('type'),
            'status_id'     => $request->query('status_id'),
            'category_id'   => $request->query('category_id'),
            'laboratory_id' => $request->query('laboratory_id'),
            'sale_condition' => $request->query('sale_condition'),
            'manage_lots'   => $request->query('manage_lots') !== null
                ? filter_var($request->query('manage_lots'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            'is_active'     => $request->query('is_active') !== null
                ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            'per_page'      => $request->query('per_page', 20),
            'page'          => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => ProductResource::collection($result['data']),
            'meta' => [
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/products/{id}',
        summary: 'Obtener producto',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Producto encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new ProductResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/catalog/products',
        summary: 'Crear producto',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'name', 'status_id', 'category_id', 'sale_condition', 'unit_id', 'presentation_id', 'route_id', 'units_per_box', 'units_per_blister', 'min_stock', 'max_stock', 'expiry_alert_days', 'manage_lots', 'allow_fraction', 'retail_margin', 'wholesale_margin'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['GENERIC', 'BRANDED']),
                    new OA\Property(property: 'name', type: 'string', maxLength: 200),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000),
                    new OA\Property(property: 'status_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'laboratory_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'sale_condition', type: 'string', enum: ['SIN_RECETA', 'CON_RECETA', 'CON_RECETA_RETENIDA']),
                    new OA\Property(property: 'sanitary_reg', type: 'string', nullable: true),
                    new OA\Property(property: 'barcode', type: 'string', nullable: true),
                    new OA\Property(property: 'unit_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'presentation_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'route_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'units_per_box', type: 'integer', minimum: 1),
                    new OA\Property(property: 'units_per_blister', type: 'integer', minimum: 1),
                    new OA\Property(property: 'location_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'min_stock', type: 'integer', minimum: 0),
                    new OA\Property(property: 'max_stock', type: 'integer', minimum: 1),
                    new OA\Property(property: 'expiry_alert_days', type: 'integer', minimum: 1),
                    new OA\Property(property: 'manage_lots', type: 'boolean'),
                    new OA\Property(property: 'allow_fraction', type: 'boolean'),
                    new OA\Property(property: 'retail_margin', type: 'number', minimum: 0, maximum: 100),
                    new OA\Property(property: 'wholesale_margin', type: 'number', minimum: 0, maximum: 100),
                    new OA\Property(property: 'ingredients', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Producto creado', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Nombre o barcode duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'                             => 'required|string|in:GENERIC,BRANDED',
            'name'                             => 'required|string|max:200',
            'description'                      => 'nullable|string|max:1000',
            'status_id'                        => 'required|uuid',
            'category_id'                      => 'required|uuid',
            'laboratory_id'                    => 'nullable|uuid',
            'sale_condition'                   => 'required|string|in:SIN_RECETA,CON_RECETA,CON_RECETA_RETENIDA',
            'sanitary_reg'                     => 'nullable|string|min:5|max:50',
            'barcode'                          => ['nullable', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'unit_id'                          => 'required|uuid',
            'presentation_id'                  => 'required|uuid',
            'route_id'                         => 'required|uuid',
            'units_per_box'                    => 'required|integer|min:1',
            'units_per_blister'                => 'required|integer|min:1',
            'location_id'                      => 'nullable|uuid',
            'min_stock'                        => 'required|integer|min:0',
            'max_stock'                        => 'required|integer|min:1',
            'expiry_alert_days'                => 'required|integer|min:1',
            'manage_lots'                      => 'required|boolean',
            'allow_fraction'                   => 'required|boolean',
            'retail_margin'                    => 'required|numeric|min:0|max:100',
            'wholesale_margin'                 => 'required|numeric|min:0|max:100',
            'ingredients'                      => 'nullable|array',
            'ingredients.*.ingredient_id'      => 'required|uuid',
            'ingredients.*.concentration'      => 'required|string|max:30',
            'ingredients.*.concentration_unit' => 'required|string|max:20',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto         = $this->controller->store([...$validated, 'actor_user_id' => $actorUserId]);

        return (new ProductResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/catalog/products/{id}',
        summary: 'Actualizar producto',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Producto actualizado', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Nombre o barcode duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name'                             => 'required|string|max:200',
            'description'                      => 'nullable|string|max:1000',
            'status_id'                        => 'required|uuid',
            'category_id'                      => 'required|uuid',
            'laboratory_id'                    => 'nullable|uuid',
            'sale_condition'                   => 'required|string|in:SIN_RECETA,CON_RECETA,CON_RECETA_RETENIDA',
            'sanitary_reg'                     => 'nullable|string|min:5|max:50',
            'barcode'                          => ['nullable', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'unit_id'                          => 'required|uuid',
            'presentation_id'                  => 'required|uuid',
            'route_id'                         => 'required|uuid',
            'units_per_box'                    => 'required|integer|min:1',
            'units_per_blister'                => 'required|integer|min:1',
            'location_id'                      => 'nullable|uuid',
            'min_stock'                        => 'required|integer|min:0',
            'max_stock'                        => 'required|integer|min:1',
            'expiry_alert_days'                => 'required|integer|min:1',
            'manage_lots'                      => 'required|boolean',
            'allow_fraction'                   => 'required|boolean',
            'retail_margin'                    => 'required|numeric|min:0|max:100',
            'wholesale_margin'                 => 'required|numeric|min:0|max:100',
            'ingredients'                      => 'nullable|array',
            'ingredients.*.ingredient_id'      => 'required|uuid',
            'ingredients.*.concentration'      => 'required|string|max:30',
            'ingredients.*.concentration_unit' => 'required|string|max:20',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto         = $this->controller->update($id, [...$validated, 'actor_user_id' => $actorUserId]);

        return (new ProductResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/products/{id}',
        summary: 'Desactivar producto',
        tags: ['Catalog · Products'],
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

    #[OA\Post(
        path: '/v1/catalog/products/{id}/images',
        summary: 'Agregar imagen al producto',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'image', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Imagen añadida', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function addImage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $imageContent = base64_encode(file_get_contents($validated['image']->path()));
        $mimeType     = $validated['image']->getMimeType();
        $actorUserId  = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->addImage($id, [
            'image_content' => $imageContent,
            'mime_type'     => $mimeType,
            'actor_user_id' => $actorUserId,
        ]);

        return (new ProductResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/products/{id}/images/{imageId}',
        summary: 'Eliminar imagen del producto',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'imageId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function removeImage(Request $request, string $id, string $imageId): JsonResponse
    {
        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $this->controller->removeImage($id, $imageId, $actorUserId);

        return response()->json(null, 204);
    }

    #[OA\Put(
        path: '/v1/catalog/products/{id}/images/reorder',
        summary: 'Reordenar imágenes del producto',
        tags: ['Catalog · Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order'],
                properties: [
                    new OA\Property(
                        property: 'order',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        description: 'IDs de imágenes en el orden deseado (primera = portada)'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function reorderImages(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'required|uuid',
        ]);

        $this->controller->reorderImages($id, $validated['order']);

        return response()->json(null, 204);
    }
}
