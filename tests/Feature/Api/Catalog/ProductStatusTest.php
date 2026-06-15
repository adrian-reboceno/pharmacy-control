<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\Status\Infrastructure\Persistence\Eloquent\Model\EloquentStatus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ProductStatusTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.statuses.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status'               => 'ACTIVE',
            'password_hash'        => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at'    => now(),
        ]);

        $this->user->givePermissionTo('catalog.statuses.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createStatus(array $overrides = []): EloquentStatus
    {
        return EloquentStatus::create(array_merge([
            'id'          => Str::uuid()->toString(),
            'name'        => 'Activo',
            'code'        => 'ACTIVO',
            'description' => 'El producto está disponible para venta.',
            'is_active'   => true,
            'created_by'  => null,
        ], $overrides));
    }

    public function test_get_statuses_returns_200_with_paginated_list(): void
    {
        $this->createStatus(['name' => 'Activo',        'code' => 'ACTIVO']);
        $this->createStatus(['name' => 'Descontinuado', 'code' => 'DESCONTINUADO']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/statuses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'code', 'description', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_statuses_returns_the_3_seeded_statuses(): void
    {
        $this->createStatus(['name' => 'Activo',        'code' => 'ACTIVO']);
        $this->createStatus(['name' => 'Descontinuado', 'code' => 'DESCONTINUADO']);
        $this->createStatus(['name' => 'Eliminado',     'code' => 'ELIMINADO']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/statuses');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 3);

        $codes = array_column($response->json('data'), 'code');
        $this->assertContains('ACTIVO', $codes);
        $this->assertContains('DESCONTINUADO', $codes);
        $this->assertContains('ELIMINADO', $codes);
    }

    public function test_get_statuses_with_search_activo_returns_matching_statuses(): void
    {
        $this->createStatus(['name' => 'Activo',        'code' => 'ACTIVO']);
        $this->createStatus(['name' => 'Descontinuado', 'code' => 'DESCONTINUADO']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/statuses?search=activo');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.name', 'Activo');
    }

    public function test_get_status_by_id_returns_200(): void
    {
        $status = $this->createStatus();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/statuses/{$status->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $status->id)
            ->assertJsonPath('data.name', 'Activo')
            ->assertJsonPath('data.code', 'ACTIVO');
    }

    public function test_get_status_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/catalog/statuses/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_statuses_returns_201_with_created_status(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/statuses', [
            'name'        => 'En revisión',
            'code'        => 'EN_REVISION',
            'description' => 'Pendiente de revisión regulatoria.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'En revisión')
            ->assertJsonPath('data.code', 'EN_REVISION')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_post_statuses_normalizes_code_to_uppercase(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/statuses', [
            'name' => 'En revisión',
            'code' => 'en_revision',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'EN_REVISION');
    }

    public function test_post_statuses_returns_409_when_name_is_duplicate_case_insensitive(): void
    {
        $this->createStatus(['name' => 'Activo', 'code' => 'ACTIVO']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/statuses', [
            'name' => 'ACTIVO',
            'code' => 'ACTIVO2',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_statuses_returns_409_when_code_is_duplicate_case_insensitive(): void
    {
        $this->createStatus(['name' => 'Activo', 'code' => 'ACTIVO']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/statuses', [
            'name' => 'Activo Nuevo',
            'code' => 'activo',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_statuses_returns_422_when_validation_fails(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/statuses', [
            'name' => '',
            'code' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_put_statuses_returns_200_with_updated_data(): void
    {
        $status = $this->createStatus(['name' => 'Activo', 'code' => 'ACTIVO']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/statuses/{$status->id}", [
            'name'        => 'Activo Modificado',
            'code'        => 'ACTIVO_MOD',
            'description' => 'Descripción actualizada.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Activo Modificado')
            ->assertJsonPath('data.code', 'ACTIVO_MOD');
    }

    public function test_delete_statuses_returns_204(): void
    {
        $status = $this->createStatus();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/statuses/{$status->id}");

        $response->assertStatus(204);
    }

    public function test_delete_statuses_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/v1/catalog/statuses/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication_token(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/statuses')->assertStatus(401);
        $this->getJson("/api/v1/catalog/statuses/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/statuses')->assertStatus(401);
        $this->putJson("/api/v1/catalog/statuses/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/statuses/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_statuses_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status'               => 'ACTIVE',
            'email_verified_at'    => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/statuses')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/statuses', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/statuses/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/statuses/{$id}")->assertStatus(403);
    }
}
