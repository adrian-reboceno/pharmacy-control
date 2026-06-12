<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Model\EloquentUnit;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class UnitOfMeasurementTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.units.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.units.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createUnit(array $overrides = []): EloquentUnit
    {
        return EloquentUnit::create(array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'Miligramo',
            'symbol' => 'mg',
            'type' => 'CONCENTRATION',
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    public function test_get_units_returns_200_with_paginated_list(): void
    {
        $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg']);
        $this->createUnit(['name' => 'Gramo',     'symbol' => 'g']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/units');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'symbol', 'type', 'type_label', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_units_with_type_concentration_returns_only_concentration_units(): void
    {
        $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg', 'type' => 'CONCENTRATION']);
        $this->createUnit(['name' => 'Pieza',     'symbol' => 'pza', 'type' => 'QUANTITY']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/units?type=CONCENTRATION');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.type', 'CONCENTRATION');
    }

    public function test_get_units_with_type_quantity_returns_only_quantity_units(): void
    {
        $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg', 'type' => 'CONCENTRATION']);
        $this->createUnit(['name' => 'Pieza',     'symbol' => 'pza', 'type' => 'QUANTITY']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/units?type=QUANTITY');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.type', 'QUANTITY');
    }

    public function test_get_units_with_search_mg_returns_matching_units(): void
    {
        $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg']);
        $this->createUnit(['name' => 'Litro',     'symbol' => 'L']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/units?search=mg');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_get_unit_by_id_returns_200(): void
    {
        $unit = $this->createUnit();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/units/{$unit->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $unit->id)
            ->assertJsonPath('data.name', 'Miligramo')
            ->assertJsonPath('data.symbol', 'mg');
    }

    public function test_get_unit_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/catalog/units/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_units_returns_201_with_created_unit(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/units', [
            'name' => 'Miligramo',
            'symbol' => 'mg',
            'type' => 'CONCENTRATION',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Miligramo')
            ->assertJsonPath('data.symbol', 'mg')
            ->assertJsonPath('data.type', 'CONCENTRATION')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_post_units_returns_409_when_name_is_duplicate_case_insensitive(): void
    {
        $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/units', [
            'name' => 'MILIGRAMO',
            'symbol' => 'MG',
            'type' => 'CONCENTRATION',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_units_returns_409_when_symbol_is_duplicate_case_sensitive(): void
    {
        $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/units', [
            'name' => 'Otro nombre',
            'symbol' => 'mg',
            'type' => 'CONCENTRATION',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_units_returns_422_when_validation_fails(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/units', [
            'name' => '',
            'symbol' => '',
            'type' => 'INVALID_TYPE',
        ]);

        $response->assertStatus(422);
    }

    public function test_put_units_returns_200_with_updated_data(): void
    {
        $unit = $this->createUnit(['name' => 'Miligramo', 'symbol' => 'mg', 'type' => 'CONCENTRATION']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/units/{$unit->id}", [
            'name' => 'Gramo',
            'symbol' => 'g',
            'type' => 'CONCENTRATION',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Gramo')
            ->assertJsonPath('data.symbol', 'g');
    }

    public function test_delete_units_returns_204(): void
    {
        $unit = $this->createUnit();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/units/{$unit->id}");

        $response->assertStatus(204);
    }

    public function test_delete_units_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/units/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication_token(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/units')->assertStatus(401);
        $this->getJson("/api/v1/catalog/units/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/units')->assertStatus(401);
        $this->putJson("/api/v1/catalog/units/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/units/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_units_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/units')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/units', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/units/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/units/{$id}")->assertStatus(403);
    }
}
