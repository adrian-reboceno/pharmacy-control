<?php

// ── ARCHIVO: tests/Feature/Api/Catalog/ClassificationTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Eloquent\Model\EloquentClassification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ClassificationTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.classifications.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->user->givePermissionTo('catalog.classifications.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createClassification(array $overrides = []): EloquentClassification
    {
        return EloquentClassification::create(array_merge([
            'id' => Str::uuid()->toString(),
            'lgs_group' => 'V',
            'name' => 'OTC exclusivo farmacias',
            'prescription_type' => 'SIN_RECETA',
            'validity_days' => null,
            'validity_note' => null,
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    private function createAllGroups(): void
    {
        $groups = [
            ['lgs_group' => 'I',    'name' => 'Estupefacientes',         'prescription_type' => 'CON_CODIGO_BARRAS', 'validity_days' => 30],
            ['lgs_group' => 'II',   'name' => 'Psicotrópicos II',        'prescription_type' => 'NORMAL',            'validity_days' => 30],
            ['lgs_group' => 'III',  'name' => 'Psicotrópicos III',       'prescription_type' => 'NORMAL',            'validity_days' => 180],
            ['lgs_group' => 'IV_A', 'name' => 'Antibióticos',            'prescription_type' => 'NORMAL',            'validity_days' => null],
            ['lgs_group' => 'IV_B', 'name' => 'Medicamentos con receta', 'prescription_type' => 'NORMAL',            'validity_days' => null],
            ['lgs_group' => 'V',    'name' => 'OTC exclusivo farmacias', 'prescription_type' => 'SIN_RECETA',        'validity_days' => null],
            ['lgs_group' => 'VI',   'name' => 'OTC libre acceso',        'prescription_type' => 'SIN_RECETA',        'validity_days' => null],
        ];

        foreach ($groups as $data) {
            EloquentClassification::create(array_merge($data, [
                'id' => Str::uuid()->toString(),
                'validity_note' => null,
                'is_active' => true,
                'created_by' => null,
            ]));
        }
    }

    public function test_get_classifications_returns_200_with_all_7_groups(): void
    {
        $this->createAllGroups();

        $response = $this->actingAsUser()->getJson('/api/v1/catalog/classifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'lgs_group', 'lgs_group_label', 'name',
                    'prescription_type', 'prescription_type_label',
                    'validity_days', 'validity_note',
                    'is_controlled', 'is_active', 'created_at', 'updated_at',
                ]],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 7);
    }

    public function test_get_classifications_filtered_by_is_controlled_true_returns_only_groups_i_to_iv(): void
    {
        $this->createAllGroups();

        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/classifications?is_controlled=true');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 5);
    }

    public function test_get_classifications_filtered_by_is_controlled_false_returns_only_groups_v_and_vi(): void
    {
        $this->createAllGroups();

        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/classifications?is_controlled=false');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_classification_by_id_returns_200(): void
    {
        $c = $this->createClassification();

        $response = $this->actingAsUser()
            ->getJson("/api/v1/catalog/classifications/{$c->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $c->id)
            ->assertJsonPath('data.lgs_group', 'V');
    }

    public function test_get_classification_by_id_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/classifications/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_put_classification_returns_200_with_updated_data(): void
    {
        $c = $this->createClassification([
            'lgs_group' => 'II',
            'name' => 'Psicotrópicos II',
            'prescription_type' => 'NORMAL',
            'validity_days' => 30,
        ]);

        $response = $this->actingAsUser()->putJson(
            "/api/v1/catalog/classifications/{$c->id}",
            [
                'name' => 'Psicotrópicos Grupo II',
                'prescription_type' => 'NORMAL',
                'validity_days' => 30,
                'validity_note' => 'Actualizado',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Psicotrópicos Grupo II')
            ->assertJsonPath('data.validity_note', 'Actualizado');
    }

    public function test_put_classification_returns_422_for_invalid_prescription_type(): void
    {
        $c = $this->createClassification();

        $response = $this->actingAsUser()->putJson(
            "/api/v1/catalog/classifications/{$c->id}",
            [
                'name' => 'Test',
                'prescription_type' => 'RECETA_MAGICA',
            ]
        );

        $response->assertStatus(422);
    }

    public function test_delete_classification_returns_204(): void
    {
        $c = $this->createClassification();

        $response = $this->actingAsUser()
            ->deleteJson("/api/v1/catalog/classifications/{$c->id}");

        $response->assertStatus(204);
    }

    public function test_delete_classification_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/classifications/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_401_without_authentication(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/catalog/classifications')->assertStatus(401);
        $this->getJson("/api/v1/catalog/classifications/{$id}")->assertStatus(401);
        $this->putJson("/api/v1/catalog/classifications/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/catalog/classifications/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_catalog_classifications_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/catalog/classifications')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/catalog/classifications/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/catalog/classifications/{$id}")->assertStatus(403);
    }

    public function test_post_classifications_returns_405_method_not_allowed(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/classifications', []);

        $response->assertStatus(405);
    }
}
