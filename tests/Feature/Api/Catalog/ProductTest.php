<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Eloquent\Model\EloquentIngredient;
use PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Model\EloquentCategory;
use PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Model\EloquentLaboratory;
use PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Eloquent\Model\EloquentPresentation;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Model\EloquentRoute;
use PharmaControl\Catalog\Status\Infrastructure\Persistence\Eloquent\Model\EloquentStatus;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Model\EloquentUnit;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class ProductTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    private EloquentStatus $status;

    private EloquentCategory $category;

    private EloquentUnit $unit;

    private EloquentPresentation $presentation;

    private EloquentRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('catalog.products.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'password_hash' => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);
        $this->user->givePermissionTo('catalog.products.manage');

        $this->status = EloquentStatus::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Activo',
            'code' => 'ACTIVO',
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->category = EloquentCategory::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Analgésicos',
            'slug' => 'analgesicos',
            'parent_id' => null,
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->unit = EloquentUnit::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Miligramo',
            'symbol' => 'mg',
            'type' => 'QUANTITY',
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->presentation = EloquentPresentation::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Tableta',
            'abbreviation' => 'Tab',
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->route = EloquentRoute::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Oral',
            'code' => 'ORAL',
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ]);
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'GENERIC',
            'name' => 'Paracetamol 500mg Tabs',
            'description' => 'Analgésico de uso común',
            'status_id' => $this->status->id,
            'category_id' => $this->category->id,
            'laboratory_id' => null,
            'sale_condition' => 'SIN_RECETA',
            'sanitary_reg' => null,
            'barcode' => null,
            'unit_id' => $this->unit->id,
            'presentation_id' => $this->presentation->id,
            'route_id' => $this->route->id,
            'units_per_box' => 20,
            'units_per_blister' => 10,
            'location_id' => null,
            'min_stock' => 10,
            'max_stock' => 200,
            'expiry_alert_days' => 30,
            'manage_lots' => true,
            'allow_fraction' => false,
            'retail_margin' => 25.0,
            'wholesale_margin' => 15.0,
            'ingredients' => [],
        ], $overrides);
    }

    public function test_post_creates_generic_product_and_returns_201(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'type', 'type_label', 'name', 'status_id',
                    'category_id', 'sale_condition', 'specs', 'stock_config',
                    'margins', 'ingredients', 'image_urls', 'is_active',
                ],
            ]);

        $response->assertJsonPath('data.type', 'GENERIC');
        $response->assertJsonPath('data.name', 'Paracetamol 500mg Tabs');
        $response->assertJsonPath('data.is_active', true);
    }

    public function test_post_returns_422_when_type_is_branded_but_no_laboratory(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload([
                'type' => 'BRANDED',
                'laboratory_id' => null,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('error', 'LABORATORY_REQUIRED');
    }

    public function test_post_returns_409_when_name_is_duplicate(): void
    {
        $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());

        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());

        $response->assertStatus(409);
    }

    public function test_post_returns_422_when_barcode_check_digit_is_wrong(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload([
                'barcode' => '7501031311300',
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('error', 'INVALID_BARCODE');
    }

    public function test_get_returns_200_with_paginated_list(): void
    {
        $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());

        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 1);
    }

    public function test_get_single_returns_200_with_product_data(): void
    {
        $create = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());
        $id = $create->json('data.id');

        $response = $this->actingAsUser()
            ->getJson("/api/v1/catalog/products/{$id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Paracetamol 500mg Tabs');
    }

    public function test_get_single_returns_404_for_unknown_id(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/products/'.Str::uuid()->toString());

        $response->assertStatus(404);
    }

    public function test_put_updates_product_and_returns_200(): void
    {
        $create = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());
        $id = $create->json('data.id');

        $response = $this->actingAsUser()
            ->putJson("/api/v1/catalog/products/{$id}", $this->validPayload([
                'name' => 'Paracetamol 1g Tabs',
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Paracetamol 1g Tabs');
    }

    public function test_delete_deactivates_product_and_returns_204(): void
    {
        $create = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());
        $id = $create->json('data.id');

        $response = $this->actingAsUser()
            ->deleteJson("/api/v1/catalog/products/{$id}");

        $response->assertStatus(204);

        $get = $this->actingAsUser()
            ->getJson("/api/v1/catalog/products/{$id}");

        $get->assertJsonPath('data.is_active', false);
    }

    public function test_delete_returns_404_for_unknown_id(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/catalog/products/'.Str::uuid()->toString());

        $response->assertStatus(404);
    }

    public function test_post_creates_product_with_ingredients(): void
    {
        $ingredient = EloquentIngredient::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Paracetamol',
            'dci_code' => 'paracetamol',
            'cas_number' => null,
            'description' => null,
            'is_active' => true,
            'created_by' => null,
        ]);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload([
                'ingredients' => [[
                    'ingredient_id' => $ingredient->id,
                    'concentration' => '500',
                    'concentration_unit' => 'mg',
                ]],
            ]));

        $response->assertStatus(201);
        $this->assertCount(1, $response->json('data.ingredients'));
        $this->assertSame('500', $response->json('data.ingredients.0.concentration'));
    }

    public function test_post_creates_branded_product_with_laboratory(): void
    {
        $laboratory = EloquentLaboratory::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Pfizer',
            'country_code' => 'US',
            'website' => null,
            'is_active' => true,
            'created_by' => null,
        ]);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload([
                'type' => 'BRANDED',
                'name' => 'Amoxicilina Pfizer 500mg',
                'laboratory_id' => $laboratory->id,
            ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'BRANDED')
            ->assertJsonPath('data.laboratory_id', $laboratory->id);
    }

    public function test_list_filters_by_type(): void
    {
        $this->actingAsUser()->postJson('/api/v1/catalog/products', $this->validPayload([
            'name' => 'Producto Genérico',
        ]));

        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/products?type=GENERIC');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/products?type=BRANDED');

        $response->assertJsonPath('meta.total', 0);
    }

    public function test_list_filters_by_is_active(): void
    {
        $create = $this->actingAsUser()
            ->postJson('/api/v1/catalog/products', $this->validPayload());
        $id = $create->json('data.id');

        $this->actingAsUser()->deleteJson("/api/v1/catalog/products/{$id}");

        $response = $this->actingAsUser()
            ->getJson('/api/v1/catalog/products?is_active=false');

        $response->assertJsonPath('meta.total', 1);
    }

    public function test_post_returns_401_without_auth(): void
    {
        $response = $this->postJson('/api/v1/catalog/products', $this->validPayload());

        $response->assertStatus(401);
    }
}
