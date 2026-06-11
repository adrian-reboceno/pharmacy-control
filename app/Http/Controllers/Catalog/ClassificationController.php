<?php

// ── ARCHIVO: app/Http/Controllers/Catalog/ClassificationController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ClassificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PharmaControl\Catalog\Classifications\Infrastructure\Controller\ClassificationController as InfraClassificationController;

#[OA\Tag(name: 'Catalog · Classifications', description: 'Grupos de medicamentos Art. 226 LGS — fijos por ley')]

#[OA\Schema(
    schema: 'ClassificationResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'lgs_group', type: 'string', enum: ['I', 'II', 'III', 'IV_A', 'IV_B', 'V', 'VI'], example: 'II'),
        new OA\Property(property: 'lgs_group_label', type: 'string', example: 'Grupo II — Psicotrópicos'),
        new OA\Property(property: 'name', type: 'string', example: 'Psicotrópicos — Grupo II'),
        new OA\Property(property: 'prescription_type', type: 'string', enum: ['CON_CODIGO_BARRAS', 'NORMAL', 'SIN_RECETA'], example: 'NORMAL'),
        new OA\Property(property: 'prescription_type_label', type: 'string', example: 'Receta normal'),
        new OA\Property(property: 'validity_days', type: 'integer', nullable: true, example: 30),
        new OA\Property(property: 'validity_note', type: 'string', nullable: true, example: 'Máximo 2 presentaciones por receta.'),
        new OA\Property(property: 'is_controlled', type: 'boolean', example: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]

class ClassificationController extends Controller
{
    public function __construct(
        private readonly InfraClassificationController $controller,
    ) {}

    #[OA\Get(
        path: '/v1/catalog/classifications',
        summary: 'Listar clasificaciones LGS',
        description: 'Devuelve los 7 grupos del Art. 226 LGS ordenados I→II→III→IV_A→IV_B→V→VI. Los grupos son fijos por ley — no se crean nuevos.',
        tags: ['Catalog · Classifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'Filtrar por estado'),
            new OA\Parameter(name: 'is_controlled', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'true = grupos I-IV, false = grupos V-VI'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de clasificaciones',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ClassificationResponse')),
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
            'is_active' => $request->query('is_active') !== null
                ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'is_controlled' => $request->query('is_controlled') !== null
                ? filter_var($request->query('is_controlled'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'per_page' => $request->query('per_page', 20),
            'page' => $request->query('page', 1),
        ]);

        return response()->json([
            'data' => ClassificationResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/v1/catalog/classifications/{id}',
        summary: 'Obtener clasificación',
        tags: ['Catalog · Classifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Clasificación encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ClassificationResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new ClassificationResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Put(
        path: '/v1/catalog/classifications/{id}',
        summary: 'Actualizar clasificación',
        description: 'Solo modifica parámetros configurables. El campo lgs_group es inmutable por ley.',
        tags: ['Catalog · Classifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'prescription_type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Psicotrópicos — Grupo II'),
                    new OA\Property(property: 'prescription_type', type: 'string', enum: ['CON_CODIGO_BARRAS', 'NORMAL', 'SIN_RECETA'], example: 'NORMAL'),
                    new OA\Property(property: 'validity_days', type: 'integer', nullable: true, minimum: 1, maximum: 365, example: 30),
                    new OA\Property(property: 'validity_note', type: 'string', nullable: true, maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Clasificación actualizada', content: new OA\JsonContent(ref: '#/components/schemas/ClassificationResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'prescription_type' => 'required|string|in:CON_CODIGO_BARRAS,NORMAL,SIN_RECETA',
            'validity_days' => 'nullable|integer|min:1|max:365',
            'validity_note' => 'nullable|string|max:255',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->update($id, [
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new ClassificationResource($dto))->response()->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/v1/catalog/classifications/{id}',
        summary: 'Desactivar clasificación',
        description: 'Soft delete lógico. No elimina el registro.',
        tags: ['Catalog · Classifications'],
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
