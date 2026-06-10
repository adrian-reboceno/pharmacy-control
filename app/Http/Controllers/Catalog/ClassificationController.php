<?php

// ── ARCHIVO: app/Http/Controllers/Catalog/ClassificationController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ClassificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PharmaControl\Catalog\Classifications\Infrastructure\Controller\ClassificationController as InfraClassificationController;

class ClassificationController extends Controller
{
    public function __construct(
        private readonly InfraClassificationController $controller,
    ) {}

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

    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new ClassificationResource($dto))->response()->setStatusCode(200);
    }

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

    public function destroy(Request $request, string $id): JsonResponse
    {
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $this->controller->destroy($id, $actorUserId);

        return response()->json(null, 204);
    }
}
