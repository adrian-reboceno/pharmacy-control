<?php

// ── ARCHIVO: tests/Feature/Api/Catalog/LaboratoryTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Model\EloquentLaboratory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class LaboratoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.laboratories.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.laboratories.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createLaboratory(array $overrides = []): EloquentLaboratory
    {
        return EloquentLaboratory::create(array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'Bayer',
            'country_code' => 'DE',
            'website' => null,
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    public function test_get_laboratories_returns_200_with_paginated_list(): void
    {
        $this->createLaboratory(['name' => 'Bayer']);
        $this->createLaboratory(['name' => 'Pfizer']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/laboratories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'country_code', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_laboratory_by_id_returns_200_with_laboratory_data(): void
    {
        $lab = $this->createLaboratory();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/laboratories/{$lab->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $lab->id)
            ->assertJsonPath('data.name', 'Bayer');
    }

    public function test_get_laboratory_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/laboratories/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_laboratories_returns_201_with_created_laboratory(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/laboratories', [
            'name' => 'Novartis',
            'country_code' => 'CH',
            'website' => 'https://www.novartis.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Novartis')
            ->assertJsonPath('data.country_code', 'CH')
            ->assertJsonPath('data.website', 'https://www.novartis.com')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_post_laboratories_returns_409_when_name_is_duplicate(): void
    {
        $this->createLaboratory(['name' => 'Novartis']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/laboratories', [
            'name' => 'Novartis',
            'country_code' => 'CH',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_laboratories_returns_422_when_validation_fails(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/laboratories', [
            'name' => '',
            'country_code' => 'DEU',
        ]);

        $response->assertStatus(422);
    }

    public function test_put_laboratory_returns_200_with_updated_data(): void
    {
        $lab = $this->createLaboratory(['name' => 'Bayer', 'country_code' => 'DE']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/laboratories/{$lab->id}", [
            'name' => 'Bayer AG',
            'country_code' => 'CH',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Bayer AG')
            ->assertJsonPath('data.country_code', 'CH');
    }

    public function test_delete_laboratory_returns_204(): void
    {
        $lab = $this->createLaboratory();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/laboratories/{$lab->id}");

        $response->assertStatus(204);
    }

    public function test_delete_laboratory_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/laboratories/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/laboratories')->assertStatus(401);
        $this->getJson("/api/v1/catalog/laboratories/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/laboratories')->assertStatus(401);
        $this->putJson("/api/v1/catalog/laboratories/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/laboratories/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_laboratories_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/laboratories')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/laboratories', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/laboratories/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/laboratories/{$id}")->assertStatus(403);
    }
}
