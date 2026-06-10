<?php

// ── ARCHIVO: app/Http/Controllers/Catalog/LaboratoryController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\LaboratoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Catalog\Laboratories\Infrastructure\Controller\LaboratoryController as InfraLaboratoryController;

class LaboratoryController extends Controller
{
    public function __construct(
        private readonly InfraLaboratoryController $controller,
    ) {}

    #[Route('GET', '/api/v1/catalog/laboratories',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    public function index(Request $request): JsonResponse
    {
        $result = $this->controller->index($request->query());

        return response()->json([
            'data' => LaboratoryResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }

    #[Route('GET', '/api/v1/catalog/laboratories/{id}',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new LaboratoryResource($dto))->response()->setStatusCode(200);
    }

    #[Route('POST', '/api/v1/catalog/laboratories',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'country_code' => 'required|string|size:2',
            'website' => 'nullable|url|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->store([
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new LaboratoryResource($dto))->response()->setStatusCode(201);
    }

    #[Route('PUT', '/api/v1/catalog/laboratories/{id}',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'country_code' => 'required|string|size:2',
            'website' => 'nullable|url|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->update($id, [
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new LaboratoryResource($dto))->response()->setStatusCode(200);
    }

    #[Route('DELETE', '/api/v1/catalog/laboratories/{id}',
        middleware: ['auth:sanctum', 'rbac2:catalog.laboratories.manage'])]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->controller->destroy($id, $request->user()->id);

        return response()->json(null, 204);
    }
}
