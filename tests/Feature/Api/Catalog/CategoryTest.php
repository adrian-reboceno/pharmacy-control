<?php

// ── ARCHIVO: tests/Feature/Api/Catalog/CategoryTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Model\EloquentCategory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.categories.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.categories.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createCategory(array $overrides = []): EloquentCategory
    {
        return EloquentCategory::create(array_merge([
            'id' => Str::uuid()->toString(),
            'parent_id' => null,
            'name' => 'Medicamentos',
            'slug' => 'medicamentos',
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    // ── Tree ─────────────────────────────────────────────────────────────────

    public function test_get_categories_tree_returns_200_with_nested_tree(): void
    {
        $parent = $this->createCategory(['name' => 'Antibióticos', 'slug' => 'antibioticos']);
        $this->createCategory([
            'name' => 'Penicilinas',
            'slug' => 'penicilinas',
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/categories/tree');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'is_active', 'is_root', 'children']]]);

        $roots = $response->json('data');
        self::assertCount(1, $roots);
        self::assertCount(1, $roots[0]['children']);
    }

    public function test_get_categories_tree_with_is_active_true_returns_only_active_nodes(): void
    {
        $this->createCategory(['name' => 'Activa', 'slug' => 'activa', 'is_active' => true]);
        $this->createCategory(['name' => 'Inactiva', 'slug' => 'inactiva', 'is_active' => false]);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/categories/tree?is_active=true');

        $response->assertStatus(200);
        self::assertCount(1, $response->json('data'));
        self::assertSame('activa', $response->json('data.0.slug'));
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function test_get_category_by_id_returns_200_with_category_data(): void
    {
        $cat = $this->createCategory();

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/categories/{$cat->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $cat->id)
            ->assertJsonPath('data.slug', 'medicamentos');
    }

    public function test_get_category_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/categories/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_post_categories_returns_201_with_created_category(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/categories', [
            'name' => 'Suplementos',
            'slug' => 'suplementos',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Suplementos')
            ->assertJsonPath('data.slug', 'suplementos')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.is_root', true);
    }

    public function test_post_categories_auto_generates_slug_when_not_provided(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/categories', [
            'name' => 'Analgésicos',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'analgesicos');
    }

    public function test_post_categories_returns_409_when_slug_is_duplicate(): void
    {
        $this->createCategory(['name' => 'Medicamentos', 'slug' => 'medicamentos']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/categories', [
            'name' => 'Medicamentos',
            'slug' => 'medicamentos',
        ]);

        $response->assertStatus(409);
    }

    public function test_post_categories_returns_422_when_name_is_missing(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/categories', []);

        $response->assertStatus(422);
    }

    public function test_post_categories_creates_child_category_with_valid_parent_id(): void
    {
        $parent = $this->createCategory(['name' => 'Antibióticos', 'slug' => 'antibioticos']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/categories', [
            'parent_id' => $parent->id,
            'name' => 'Penicilinas',
            'slug' => 'penicilinas',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('data.is_root', false);
    }

    public function test_post_categories_returns_404_when_parent_id_does_not_exist(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/categories', [
            'parent_id' => '00000000-0000-4000-8000-000000000000',
            'name' => 'Penicilinas',
        ]);

        $response->assertStatus(404);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function test_put_category_returns_200_with_updated_data(): void
    {
        $cat = $this->createCategory(['name' => 'Antibióticos', 'slug' => 'antibioticos']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/categories/{$cat->id}", [
            'name' => 'Antibióticos Actualizado',
            'slug' => 'antibioticos-actualizado',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Antibióticos Actualizado')
            ->assertJsonPath('data.slug', 'antibioticos-actualizado');
    }

    public function test_put_category_returns_422_when_update_would_create_cycle(): void
    {
        $parent = $this->createCategory(['name' => 'Padre', 'slug' => 'padre']);
        $child = $this->createCategory([
            'name' => 'Hijo',
            'slug' => 'hijo',
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/categories/{$parent->id}", [
            'name' => 'Padre',
            'slug' => 'padre',
            'parent_id' => $child->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'CYCLE_DETECTED');
    }

    public function test_put_category_returns_422_when_parent_is_self(): void
    {
        $cat = $this->createCategory();

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/categories/{$cat->id}", [
            'name' => 'Medicamentos',
            'slug' => 'medicamentos',
            'parent_id' => $cat->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'CYCLE_DETECTED');
    }

    // ── Destroy ─────────────────────────────────────────────────────────────────

    public function test_delete_category_returns_204(): void
    {
        $cat = $this->createCategory();

        $response = $this->actingAsUser()->deleteJson("/api/v1/catalog/categories/{$cat->id}");

        $response->assertStatus(204);
    }

    public function test_delete_category_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/categories/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_delete_category_does_not_deactivate_children(): void
    {
        $parent = $this->createCategory(['name' => 'Padre', 'slug' => 'padre']);
        $child = $this->createCategory([
            'name' => 'Hijo',
            'slug' => 'hijo',
            'parent_id' => $parent->id,
        ]);

        $this->actingAsUser()->deleteJson("/api/v1/catalog/categories/{$parent->id}");

        $child->refresh();
        self::assertTrue($child->is_active);
    }

    // ── Auth / Permissions ─────────────────────────────────────────────────────

    public function test_all_endpoints_return_401_without_authentication_token(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/categories/tree')->assertStatus(401);
        $this->getJson("/api/v1/catalog/categories/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/categories')->assertStatus(401);
        $this->putJson("/api/v1/catalog/categories/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/categories/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_categories_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/categories/tree')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson("/api/v1/catalog/categories/{$id}")->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/catalog/categories', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/categories/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/categories/{$id}")->assertStatus(403);
    }
}
