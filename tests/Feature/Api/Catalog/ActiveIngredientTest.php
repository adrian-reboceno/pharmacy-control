<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Eloquent\Model\EloquentIngredient;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ActiveIngredientTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.active-ingredients.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.active-ingredients.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createIngredient(array $overrides = []): EloquentIngredient
    {
        return EloquentIngredient::create(array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'Amoxicilina',
            'dci_code' => 'amoxicillin',
            'cas_number' => '26787-78-0',
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    public function test_get_active_ingredients_returns_200_with_paginated_list(): void
    {
        $this->createIngredient();
        $this->createIngredient(['name' => 'Ibuprofeno', 'dci_code' => 'ibuprofen', 'cas_number' => '15687-27-1']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/active-ingredients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'dci_code', 'cas_number', 'description', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_active_ingredients_search_amox_returns_amoxicillin_results(): void
    {
        $this->createIngredient(['name' => 'Amoxicilina', 'dci_code' => 'amoxicillin', 'cas_number' => null]);
        $this->createIngredient(['name' => 'Ibuprofeno',  'dci_code' => 'ibuprofen',   'cas_number' => '15687-27-1']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/active-ingredients?search=amox');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.name', 'Amoxicilina');
    }

    public function test_get_active_ingredient_by_id_returns_200(): void
    {
        $ingredient = $this->createIngredient();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/active-ingredients/{$ingredient->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ingredient->id)
            ->assertJsonPath('data.name', 'Amoxicilina')
            ->assertJsonPath('data.dci_code', 'amoxicillin');
    }

    public function test_get_active_ingredient_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/catalog/active-ingredients/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_active_ingredients_returns_201_with_cas_number(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', [
            'name' => 'Paracetamol',
            'dci_code' => 'paracetamol',
            'cas_number' => '103-90-2',
            'description' => 'Analgésico y antipirético.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Paracetamol')
            ->assertJsonPath('data.dci_code', 'paracetamol')
            ->assertJsonPath('data.cas_number', '103-90-2')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_post_active_ingredients_returns_201_without_cas_number(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', [
            'name' => 'Amoxicilina+Ácido Clavulánico',
            'dci_code' => 'amoxicillin+clavulanate',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Amoxicilina+Ácido Clavulánico')
            ->assertJsonPath('data.cas_number', null);
    }

    public function test_post_active_ingredients_returns_409_when_name_is_duplicate_case_insensitive(): void
    {
        $this->createIngredient(['name' => 'Amoxicilina', 'dci_code' => 'amoxicillin', 'cas_number' => null]);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', [
            'name' => 'AMOXICILINA',
            'dci_code' => 'amoxicillin-other',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_active_ingredients_returns_409_when_dci_code_is_duplicate_case_insensitive(): void
    {
        $this->createIngredient(['name' => 'Amoxicilina', 'dci_code' => 'amoxicillin', 'cas_number' => null]);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', [
            'name' => 'Amoxicilina Nueva',
            'dci_code' => 'AMOXICILLIN',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_active_ingredients_returns_409_when_cas_number_is_duplicate(): void
    {
        $this->createIngredient(['name' => 'Amoxicilina', 'dci_code' => 'amoxicillin', 'cas_number' => '26787-78-0']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', [
            'name' => 'Otro Ingrediente',
            'dci_code' => 'other-ingredient',
            'cas_number' => '26787-78-0',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_active_ingredients_returns_422_when_cas_number_format_is_invalid(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', [
            'name' => 'Paracetamol',
            'dci_code' => 'paracetamol',
            'cas_number' => '1039-0',
        ]);

        $response->assertStatus(422);
    }

    public function test_post_active_ingredients_returns_422_when_required_fields_missing(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/active-ingredients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'dci_code']);
    }

    public function test_put_active_ingredients_returns_200_with_updated_data(): void
    {
        $ingredient = $this->createIngredient(['cas_number' => null]);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/active-ingredients/{$ingredient->id}", [
            'name' => 'Amoxicilina Modificada',
            'dci_code' => 'amoxicillin modified',
            'description' => 'Descripción actualizada.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Amoxicilina Modificada')
            ->assertJsonPath('data.dci_code', 'amoxicillin modified');
    }

    public function test_delete_active_ingredients_returns_204(): void
    {
        $ingredient = $this->createIngredient();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/active-ingredients/{$ingredient->id}");

        $response->assertStatus(204);
    }

    public function test_delete_active_ingredients_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/v1/catalog/active-ingredients/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication_token(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/active-ingredients')->assertStatus(401);
        $this->getJson("/api/v1/catalog/active-ingredients/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/active-ingredients')->assertStatus(401);
        $this->putJson("/api/v1/catalog/active-ingredients/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/active-ingredients/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_active_ingredients_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/active-ingredients')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/active-ingredients', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/active-ingredients/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/active-ingredients/{$id}")->assertStatus(403);
    }
}
