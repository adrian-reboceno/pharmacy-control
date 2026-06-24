<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\Location\Infrastructure\Persistence\Eloquent\Model\EloquentLocation;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class LocationTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.locations.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.locations.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createZona(array $overrides = []): EloquentLocation
    {
        return EloquentLocation::create(array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'OTC General',
            'level' => 1,
            'parent_id' => null,
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    private function createChild(int $level, string $parentId, ?string $name = null): EloquentLocation
    {
        $levelNames = [2 => 'Pasillo 1', 3 => 'Estante 1', 4 => 'Pos 1'];

        return EloquentLocation::create([
            'id' => Str::uuid()->toString(),
            'name' => $name ?? $levelNames[$level],
            'level' => $level,
            'parent_id' => $parentId,
            'is_active' => true,
            'created_by' => null,
        ]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_get_locations_returns_200_with_flat_list(): void
    {
        $this->createZona();

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/locations');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'level', 'level_label', 'is_active', 'is_leaf', 'full_path']]]);
    }

    public function test_get_locations_filters_by_level(): void
    {
        $zona = $this->createZona();
        $pasillo = $this->createChild(2, $zona->id);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/locations?level=2');

        $response->assertStatus(200);
        $data = $response->json('data');
        self::assertCount(1, $data);
        self::assertSame(2, $data[0]['level']);
    }

    public function test_get_locations_filters_by_is_active(): void
    {
        $this->createZona(['name' => 'Activa', 'is_active' => true]);
        $this->createZona(['name' => 'Inactiva', 'is_active' => false]);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/locations?is_active=true');

        $response->assertStatus(200);
        self::assertCount(1, $response->json('data'));
    }

    // ── Tree ──────────────────────────────────────────────────────────────────

    public function test_get_locations_tree_returns_nested_structure(): void
    {
        $zona = $this->createZona();
        $pasillo = $this->createChild(2, $zona->id);
        $estante = $this->createChild(3, $pasillo->id);
        $this->createChild(4, $estante->id);

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/locations/tree');

        $response->assertStatus(200);
        $roots = $response->json('data');
        self::assertCount(1, $roots);
        self::assertCount(1, $roots[0]['children']);
        self::assertCount(1, $roots[0]['children'][0]['children']);
        self::assertCount(1, $roots[0]['children'][0]['children'][0]['children']);
    }

    // ── Leaves ────────────────────────────────────────────────────────────────

    public function test_get_locations_leaves_returns_only_posiciones(): void
    {
        $zona = $this->createZona();
        $pasillo = $this->createChild(2, $zona->id);
        $estante = $this->createChild(3, $pasillo->id);
        $this->createChild(4, $estante->id, 'Pos 1');
        $this->createChild(4, $estante->id, 'Pos 2');

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/locations/leaves');

        $response->assertStatus(200);
        $data = $response->json('data');
        self::assertCount(2, $data);
        foreach ($data as $item) {
            self::assertSame(4, $item['level']);
            self::assertTrue($item['is_leaf']);
        }
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_get_location_by_id_returns_200_with_full_path(): void
    {
        $zona = $this->createZona(['name' => 'Refrigeración']);
        $pasillo = $this->createChild(2, $zona->id, 'Pasillo A');

        $response = $this->actingAsUser()->getJson("/api/v1/catalog/locations/{$pasillo->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $pasillo->id)
            ->assertJsonPath('data.level', 2)
            ->assertJsonPath('data.full_path', 'Refrigeración > Pasillo A');
    }

    public function test_get_location_by_id_returns_404_when_not_found(): void
    {
        $this->actingAsUser()
            ->getJson('/api/v1/catalog/locations/00000000-0000-4000-8000-000000000000')
            ->assertStatus(404);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_post_locations_creates_zona_without_parent(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/locations', [
            'name' => 'Controlados',
            'level' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Controlados')
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.level_label', 'Zona')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.is_leaf', false)
            ->assertJsonPath('data.full_path', 'Controlados');
    }

    public function test_post_locations_creates_pasillo_as_child_of_zona(): void
    {
        $zona = $this->createZona();

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/locations', [
            'name' => 'Pasillo A',
            'level' => 2,
            'parent_id' => $zona->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.parent_id', $zona->id)
            ->assertJsonPath('data.level', 2);
    }

    public function test_post_locations_returns_422_when_level_mismatches_parent(): void
    {
        $zona = $this->createZona();

        // Zona's expected child is PASILLO(2), we send ESTANTE(3)
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/locations', [
            'name' => 'Estante 1',
            'level' => 3,
            'parent_id' => $zona->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'INVALID_LEVEL');
    }

    public function test_post_locations_returns_422_when_non_zona_has_no_parent(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/locations', [
            'name' => 'Pasillo A',
            'level' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'INVALID_LEVEL');
    }

    public function test_post_locations_returns_404_when_parent_not_found(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/catalog/locations', [
            'name' => 'Pasillo A',
            'level' => 2,
            'parent_id' => '00000000-0000-4000-8000-000000000000',
        ]);

        $response->assertStatus(404);
    }

    public function test_post_locations_returns_409_when_name_is_duplicate_among_siblings(): void
    {
        $this->createZona(['name' => 'OTC General']);

        $response = $this->actingAsUser()->postJson('/api/v1/catalog/locations', [
            'name' => 'OTC General',
            'level' => 1,
        ]);

        $response->assertStatus(409);
    }

    public function test_post_locations_returns_422_when_required_fields_missing(): void
    {
        $this->actingAsUser()->postJson('/api/v1/catalog/locations', [])
            ->assertStatus(422);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_put_location_returns_200_with_updated_data(): void
    {
        $zona = $this->createZona(['name' => 'OTC General']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/locations/{$zona->id}", [
            'name' => 'OTC General Actualizado',
            'description' => 'Nueva descripción',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'OTC General Actualizado')
            ->assertJsonPath('data.description', 'Nueva descripción');
    }

    public function test_put_location_returns_409_when_name_is_duplicate_among_siblings(): void
    {
        $zona1 = $this->createZona(['name' => 'OTC General']);
        $zona2 = $this->createZona(['name' => 'Refrigeración']);

        $response = $this->actingAsUser()->putJson("/api/v1/catalog/locations/{$zona2->id}", [
            'name' => 'OTC General',
        ]);

        $response->assertStatus(409);
    }

    public function test_put_location_returns_404_when_not_found(): void
    {
        $this->actingAsUser()
            ->putJson('/api/v1/catalog/locations/00000000-0000-4000-8000-000000000000', [
                'name' => 'Test',
            ])
            ->assertStatus(404);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_delete_location_returns_204_and_deactivates(): void
    {
        $zona = $this->createZona();

        $this->actingAsUser()
            ->deleteJson("/api/v1/catalog/locations/{$zona->id}")
            ->assertStatus(204);

        $zona->refresh();
        self::assertFalse($zona->is_active);
    }

    public function test_delete_location_does_not_deactivate_children(): void
    {
        $zona = $this->createZona();
        $pasillo = $this->createChild(2, $zona->id);

        $this->actingAsUser()->deleteJson("/api/v1/catalog/locations/{$zona->id}");

        $pasillo->refresh();
        self::assertTrue($pasillo->is_active);
    }

    public function test_delete_location_returns_404_when_not_found(): void
    {
        $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/locations/00000000-0000-4000-8000-000000000000')
            ->assertStatus(404);
    }

    // ── Auth / Permissions ────────────────────────────────────────────────────

    public function test_all_endpoints_return_401_without_authentication(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/locations')->assertStatus(401);
        $this->getJson('/api/v1/catalog/locations/tree')->assertStatus(401);
        $this->getJson('/api/v1/catalog/locations/leaves')->assertStatus(401);
        $this->getJson("/api/v1/catalog/locations/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/catalog/locations')->assertStatus(401);
        $this->putJson("/api/v1/catalog/locations/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/locations/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_permission(): void
    {
        $noPermUser = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/v1/catalog/locations')->assertStatus(403);

        $this->actingAs($noPermUser, 'sanctum')
            ->postJson('/api/v1/catalog/locations', [])->assertStatus(403);

        $this->actingAs($noPermUser, 'sanctum')
            ->putJson("/api/v1/catalog/locations/{$id}", [])->assertStatus(403);

        $this->actingAs($noPermUser, 'sanctum')
            ->deleteJson("/api/v1/catalog/locations/{$id}")->assertStatus(403);
    }
}
