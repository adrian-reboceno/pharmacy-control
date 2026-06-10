<?php

// ── ARCHIVO: app/Http/Controllers/Catalog/CategoryController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\CategoryResource;
use App\Http\Resources\Catalog\CategoryTreeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PharmaControl\Catalog\Categories\Infrastructure\Controller\CategoryController as InfraCategoryController;

class CategoryController extends Controller
{
    public function __construct(
        private readonly InfraCategoryController $controller,
    ) {}

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

    public function show(string $id): JsonResponse
    {
        $dto = $this->controller->show($id);

        return (new CategoryResource($dto))->response()->setStatusCode(200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id'   => 'nullable|uuid',
            'name'        => 'required|string|max:120',
            'slug'        => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => 'nullable|string|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->store([
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new CategoryResource($dto))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'parent_id'   => 'nullable|uuid',
            'name'        => 'required|string|max:120',
            'slug'        => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'description' => 'nullable|string|max:500',
        ]);

        $actorUserId = $request->attributes->get('authenticated_user')->userId;

        $dto = $this->controller->update($id, [
            ...$validated,
            'actor_user_id' => $actorUserId,
        ]);

        return (new CategoryResource($dto))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $actorUserId = $request->attributes->get('authenticated_user')->userId;
        $this->controller->destroy($id, $actorUserId);

        return response()->json(null, 204);
    }
}
