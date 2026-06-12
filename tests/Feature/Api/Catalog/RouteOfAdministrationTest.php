<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Model\EloquentRoute;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class RouteOfAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.routes-of-administration.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.routes-of-administration.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createRoute(array $overrides = []): EloquentRoute
    {
        return EloquentRoute::create(array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'Oral',
            'code' => 'VO',
            'description' => 'Administración por la boca.',
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    public function test_get_routes_of_administration_returns_200_with_paginated_list(): void
    {
        $this->createRoute(['name' => 'Oral',        'code' => 'VO']);
        $this->createRoute(['name' => 'Intravenosa', 'code' => 'IV']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/routes-of-administration');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'code', 'description', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_routes_of_administration_with_search_oral_returns_matching_routes(): void
    {
        $this->createRoute(['name' => 'Oral',        'code' => 'VO']);
        $this->createRoute(['name' => 'Intravenosa', 'code' => 'IV']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/routes-of-administration?search=oral');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.name', 'Oral');
    }

    public function test_get_route_of_administration_by_id_returns_200(): void
    {
        $route = $this->createRoute();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/routes-of-administration/{$route->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $route->id)
            ->assertJsonPath('data.name', 'Oral')
            ->assertJsonPath('data.code', 'VO');
    }

    public function test_get_route_of_administration_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/catalog/routes-of-administration/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_routes_of_administration_returns_201_with_created_route(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/routes-of-administration', [
            'name' => 'Sublingual',
            'code' => 'SL',
            'description' => 'Administración bajo la lengua.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Sublingual')
            ->assertJsonPath('data.code', 'SL')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_post_routes_of_administration_normalizes_code_to_uppercase(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/routes-of-administration', [
            'name' => 'Sublingual',
            'code' => 'sl',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'SL');
    }

    public function test_post_routes_of_administration_returns_409_when_name_is_duplicate_case_insensitive(): void
    {
        $this->createRoute(['name' => 'Oral', 'code' => 'VO']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/routes-of-administration', [
            'name' => 'ORAL',
            'code' => 'VO2',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_routes_of_administration_returns_409_when_code_is_duplicate_case_insensitive(): void
    {
        $this->createRoute(['name' => 'Oral', 'code' => 'VO']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/routes-of-administration', [
            'name' => 'Oral Nueva',
            'code' => 'vo',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_routes_of_administration_returns_422_when_validation_fails(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/routes-of-administration', [
            'name' => '',
            'code' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_put_routes_of_administration_returns_200_with_updated_data(): void
    {
        $route = $this->createRoute(['name' => 'Oral', 'code' => 'VO']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/routes-of-administration/{$route->id}", [
            'name' => 'Sublingual',
            'code' => 'SL',
            'description' => 'Bajo la lengua.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Sublingual')
            ->assertJsonPath('data.code', 'SL');
    }

    public function test_delete_routes_of_administration_returns_204(): void
    {
        $route = $this->createRoute();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/routes-of-administration/{$route->id}");

        $response->assertStatus(204);
    }

    public function test_delete_routes_of_administration_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/routes-of-administration/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication_token(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/routes-of-administration')->assertStatus(401);
        $this->getJson("/api/v1/catalog/routes-of-administration/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/routes-of-administration')->assertStatus(401);
        $this->putJson("/api/v1/catalog/routes-of-administration/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/routes-of-administration/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_routes_of_administration_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/routes-of-administration')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/routes-of-administration', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/routes-of-administration/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/routes-of-administration/{$id}")->assertStatus(403);
    }
}
