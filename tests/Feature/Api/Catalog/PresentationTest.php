<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Eloquent\Model\EloquentPresentation;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class PresentationTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.presentations.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status'               => 'ACTIVE',
            'password_hash'        => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at'    => now(),
        ]);

        $this->user->givePermissionTo('catalog.presentations.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createPresentation(array $overrides = []): EloquentPresentation
    {
        return EloquentPresentation::create(array_merge([
            'id'           => Str::uuid()->toString(),
            'name'         => 'Tableta',
            'abbreviation' => 'Tab',
            'description'  => 'Forma sólida oral.',
            'is_active'    => true,
            'created_by'   => null,
        ], $overrides));
    }

    public function test_get_presentations_returns_200_with_paginated_list(): void
    {
        $this->createPresentation(['name' => 'Tableta',  'abbreviation' => 'Tab']);
        $this->createPresentation(['name' => 'Cápsula',  'abbreviation' => 'Cap']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/presentations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'abbreviation', 'description', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_presentations_with_search_tab_returns_matching_presentations(): void
    {
        $this->createPresentation(['name' => 'Tableta',  'abbreviation' => 'Tab']);
        $this->createPresentation(['name' => 'Cápsula',  'abbreviation' => 'Cap']);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/presentations?search=Tab');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.abbreviation', 'Tab');
    }

    public function test_get_presentation_by_id_returns_200(): void
    {
        $presentation = $this->createPresentation();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/presentations/{$presentation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $presentation->id)
            ->assertJsonPath('data.name', 'Tableta')
            ->assertJsonPath('data.abbreviation', 'Tab');
    }

    public function test_get_presentation_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/catalog/presentations/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_presentations_returns_201_with_created_presentation(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/presentations', [
            'name'         => 'Jarabe',
            'abbreviation' => 'Jar',
            'description'  => 'Solución oral azucarada.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Jarabe')
            ->assertJsonPath('data.abbreviation', 'Jar')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_post_presentations_returns_409_when_name_is_duplicate_case_insensitive(): void
    {
        $this->createPresentation(['name' => 'Tableta', 'abbreviation' => 'Tab']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/presentations', [
            'name'         => 'TABLETA',
            'abbreviation' => 'Tab2',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_presentations_returns_409_when_abbreviation_is_duplicate_case_insensitive(): void
    {
        $this->createPresentation(['name' => 'Tableta', 'abbreviation' => 'Tab']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/presentations', [
            'name'         => 'Tableta nueva',
            'abbreviation' => 'TAB',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_presentations_returns_422_when_validation_fails(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/presentations', [
            'name'         => '',
            'abbreviation' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_put_presentations_returns_200_with_updated_data(): void
    {
        $presentation = $this->createPresentation(['name' => 'Tableta', 'abbreviation' => 'Tab']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/presentations/{$presentation->id}", [
            'name'         => 'Tableta recubierta',
            'abbreviation' => 'Tab.Rec',
            'description'  => 'Tableta con cubierta.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Tableta recubierta')
            ->assertJsonPath('data.abbreviation', 'Tab.Rec');
    }

    public function test_delete_presentations_returns_204(): void
    {
        $presentation = $this->createPresentation();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/presentations/{$presentation->id}");

        $response->assertStatus(204);
    }

    public function test_delete_presentations_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/presentations/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication_token(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/presentations')->assertStatus(401);
        $this->getJson("/api/v1/catalog/presentations/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/presentations')->assertStatus(401);
        $this->putJson("/api/v1/catalog/presentations/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/presentations/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_presentations_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status'               => 'ACTIVE',
            'email_verified_at'    => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/presentations')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/presentations', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/presentations/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/presentations/{$id}")->assertStatus(403);
    }
}
