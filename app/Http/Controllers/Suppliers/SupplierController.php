<?php

declare(strict_types=1);

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Suppliers\SupplierResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Suppliers\Infrastructure\Controller\SupplierController as InfraSupplierController;

#[OA\Tag(name: 'Suppliers', description: 'Proveedores — personas morales y físicas')]
#[OA\Schema(
    schema: 'AddressResponse',
    properties: [
        new OA\Property(property: 'street',       type: 'string', example: 'Av. Reforma'),
        new OA\Property(property: 'ext_number',   type: 'string', example: '123'),
        new OA\Property(property: 'int_number',   type: 'string', nullable: true, example: 'A'),
        new OA\Property(property: 'neighborhood', type: 'string', example: 'Centro'),
        new OA\Property(property: 'municipality', type: 'string', example: 'Puebla'),
        new OA\Property(property: 'state',        type: 'string', example: 'Puebla'),
        new OA\Property(property: 'postal_code',  type: 'string', example: '72000'),
        new OA\Property(property: 'country',      type: 'string', example: 'MX'),
        new OA\Property(property: 'full_address', type: 'string', example: 'Av. Reforma 123, Centro, Puebla, Puebla, CP 72000, MX'),
    ]
)]
#[OA\Schema(
    schema: 'SupplierResponse',
    properties: [
        new OA\Property(property: 'id',         type: 'string', format: 'uuid'),
        new OA\Property(property: 'type',       type: 'string', enum: ['MORAL', 'FISICA'], example: 'MORAL'),
        new OA\Property(property: 'type_label', type: 'string', example: 'Persona Moral'),
        new OA\Property(property: 'rfc',        type: 'string', nullable: true, example: 'ABC123456XYZ'),
        new OA\Property(property: 'legal_name', type: 'string', example: 'Distribuidora Farmacéutica del Centro S.A. de C.V.'),
        new OA\Property(property: 'trade_name', type: 'string', nullable: true, example: 'DFC'),
        new OA\Property(property: 'address',    ref: '#/components/schemas/AddressResponse'),
        new OA\Property(property: 'phone',      type: 'string', nullable: true, example: '2221234567'),
        new OA\Property(property: 'email',      type: 'string', nullable: true, example: 'contacto@dfc.mx'),
        new OA\Property(property: 'is_active',  type: 'boolean', example: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AddressRequest',
    required: ['street', 'ext_number', 'neighborhood', 'municipality', 'state', 'postal_code'],
    properties: [
        new OA\Property(property: 'street',       type: 'string', maxLength: 150, example: 'Av. Reforma'),
        new OA\Property(property: 'ext_number',   type: 'string', maxLength: 20,  example: '123'),
        new OA\Property(property: 'int_number',   type: 'string', nullable: true, maxLength: 20, example: 'A'),
        new OA\Property(property: 'neighborhood', type: 'string', maxLength: 100, example: 'Centro'),
        new OA\Property(property: 'municipality', type: 'string', maxLength: 100, example: 'Puebla'),
        new OA\Property(property: 'state',        type: 'string', example: 'Puebla'),
        new OA\Property(property: 'postal_code',  type: 'string', pattern: '^[0-9]{5}$', example: '72000'),
        new OA\Property(property: 'country',      type: 'string', maxLength: 2, example: 'MX'),
    ]
)]
class SupplierController extends Controller
{
    public function __construct(
        private readonly InfraSupplierController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/suppliers',
        summary: 'Listar proveedores',
        description: 'Lista paginada con filtros opcionales por nombre, RFC, tipo y estado.',
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search',    in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Búsqueda en razón social, nombre comercial o RFC'),
            new OA\Parameter(name: 'type',      in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['MORAL', 'FISICA'])),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SupplierResponse')),
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
            'type'      => $request->query('type'),
            'is_active' => $isActive,
            'per_page'  => $request->query('per_page', 20),
            'page'      => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => SupplierResource::collection($result['data']),
            'meta' => [
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/suppliers/{id}',
        summary: 'Obtener proveedor',
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Proveedor encontrado', content: new OA\JsonContent(ref: '#/components/schemas/SupplierResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new SupplierResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Post(
        path: '/v1/suppliers',
        summary: 'Crear proveedor',
        description: 'El RFC es opcional (proveedores extranjeros). Si se envía, debe coincidir con la longitud del type: MORAL=12, FISICA=13.',
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'legal_name', 'address'],
                properties: [
                    new OA\Property(property: 'type',       type: 'string', enum: ['MORAL', 'FISICA'], example: 'MORAL'),
                    new OA\Property(property: 'rfc',        type: 'string', nullable: true, example: 'ABC123456XYZ'),
                    new OA\Property(property: 'legal_name', type: 'string', maxLength: 200),
                    new OA\Property(property: 'trade_name', type: 'string', nullable: true, maxLength: 150),
                    new OA\Property(property: 'address',    ref: '#/components/schemas/AddressRequest'),
                    new OA\Property(property: 'phone',      type: 'string', nullable: true, example: '2221234567'),
                    new OA\Property(property: 'email',      type: 'string', nullable: true, format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Proveedor creado', content: new OA\JsonContent(ref: '#/components/schemas/SupplierResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'RFC duplicado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validación fallida o RFC con formato inválido para el type', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'                 => 'required|string|in:MORAL,FISICA',
            'rfc'                  => 'nullable|string|max:13',
            'legal_name'           => 'required|string|max:200',
            'trade_name'           => 'nullable|string|max:150',
            'address.street'       => 'required|string|max:150',
            'address.ext_number'   => 'required|string|max:20',
            'address.int_number'   => 'nullable|string|max:20',
            'address.neighborhood' => 'required|string|max:100',
            'address.municipality' => 'required|string|max:100',
            'address.state'        => 'required|string',
            'address.postal_code'  => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'address.country'      => 'nullable|string|max:2',
            'phone'                => 'nullable|string',
            'email'                => 'nullable|email',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto         = $this->controller->store([...$validated, 'actor_user_id' => $actorUserId]);

        return (new SupplierResource($dto))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/v1/suppliers/{id}',
        summary: 'Actualizar proveedor',
        description: 'type y rfc son inmutables tras la creación — no se incluyen en este endpoint.',
        tags: ['Suppliers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['legal_name', 'address'],
                properties: [
                    new OA\Property(property: 'legal_name', type: 'string', maxLength: 200),
                    new OA\Property(property: 'trade_name', type: 'string', nullable: true, maxLength: 150),
                    new OA\Property(property: 'address',    ref: '#/components/schemas/AddressRequest'),
                    new OA\Property(property: 'phone',      type: 'string', nullable: true),
                    new OA\Property(property: 'email',      type: 'string', nullable: true, format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Proveedor actualizado', content: new OA\JsonContent(ref: '#/components/schemas/SupplierResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'legal_name'           => 'required|string|max:200',
            'trade_name'           => 'nullable|string|max:150',
            'address.street'       => 'required|string|max:150',
            'address.ext_number'   => 'required|string|max:20',
            'address.int_number'   => 'nullable|string|max:20',
            'address.neighborhood' => 'required|string|max:100',
            'address.municipality' => 'required|string|max:100',
            'address.state'        => 'required|string',
            'address.postal_code'  => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'address.country'      => 'nullable|string|max:2',
            'phone'                => 'nullable|string',
            'email'                => 'nullable|email',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $dto         = $this->controller->update($id, [...$validated, 'actor_user_id' => $actorUserId]);

        return (new SupplierResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/suppliers/{id}',
        summary: 'Desactivar proveedor',
        description: 'Soft delete lógico. No elimina el registro.',
        tags: ['Suppliers'],
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
