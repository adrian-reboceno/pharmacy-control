<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Suppliers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Suppliers\Infrastructure\Persistence\Eloquent\Model\EloquentSupplier;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('suppliers.manage', 'sanctum');

        $this->user = EloquentUser::factory()->create([
            'status'               => 'ACTIVE',
            'password_hash'        => Hash::make('Secret123!'),
            'must_change_password' => false,
            'email_verified_at'    => now(),
        ]);

        $this->user->givePermissionTo('suppliers.manage');
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createSupplier(array $overrides = []): EloquentSupplier
    {
        return EloquentSupplier::create(array_merge([
            'id'                   => Str::uuid()->toString(),
            'type'                 => 'MORAL',
            'rfc'                  => 'ABC123456XYZ',
            'legal_name'           => 'Distribuidora Farmacéutica S.A. de C.V.',
            'trade_name'           => null,
            'address_street'       => 'Av. Reforma',
            'address_ext_number'   => '123',
            'address_int_number'   => null,
            'address_neighborhood' => 'Centro',
            'address_municipality' => 'Puebla',
            'address_state'        => 'Puebla',
            'address_postal_code'  => '72000',
            'address_country'      => 'MX',
            'phone'                => null,
            'email'                => null,
            'is_active'            => true,
            'created_by'           => null,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type'       => 'MORAL',
            'rfc'        => 'XYZ987654ABC',
            'legal_name' => 'Nueva Distribuidora S.A. de C.V.',
            'address'    => [
                'street'       => 'Calle 5',
                'ext_number'   => '10',
                'neighborhood' => 'Norte',
                'municipality' => 'Puebla',
                'state'        => 'Puebla',
                'postal_code'  => '72010',
                'country'      => 'MX',
            ],
        ], $overrides);
    }

    // ── GET /v1/suppliers ──────────────────────────────────────────────────────

    public function test_index_returns_200_with_paginated_list(): void
    {
        $this->createSupplier(['rfc' => 'ABC123456XYZ', 'legal_name' => 'Proveedor Alfa S.A.']);
        $this->createSupplier(['rfc' => 'DEF123456XYZ', 'legal_name' => 'Proveedor Beta S.A.']);

        $response = $this->actingAsUser()->getJson('/api/v1/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'type', 'type_label', 'rfc', 'legal_name', 'trade_name',
                    'address', 'phone', 'email', 'is_active', 'created_at', 'updated_at',
                ]],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $response->assertJsonPath('meta.total', 2);
    }

    public function test_index_filters_by_type(): void
    {
        $this->createSupplier(['type' => 'MORAL',  'rfc' => 'ABC123456XYZ', 'legal_name' => 'Moral S.A.']);
        $this->createSupplier(['type' => 'FISICA', 'rfc' => 'ABCD12345678X', 'legal_name' => 'Física S.A.']);

        $response = $this->actingAsUser()->getJson('/api/v1/suppliers?type=MORAL');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.type', 'MORAL');
    }

    public function test_index_filters_by_is_active_false(): void
    {
        $this->createSupplier(['rfc' => 'ABC123456XYZ', 'legal_name' => 'Activo S.A.',   'is_active' => true]);
        $this->createSupplier(['rfc' => 'DEF123456XYZ', 'legal_name' => 'Inactivo S.A.', 'is_active' => false]);

        $response = $this->actingAsUser()->getJson('/api/v1/suppliers?is_active=false');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.legal_name', 'Inactivo S.A.');
    }

    public function test_index_searches_by_legal_name(): void
    {
        $this->createSupplier(['rfc' => 'ABC123456XYZ', 'legal_name' => 'Farmacia del Norte']);
        $this->createSupplier(['rfc' => 'DEF123456XYZ', 'legal_name' => 'Distribuidora Sur']);

        $response = $this->actingAsUser()->getJson('/api/v1/suppliers?search=norte');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.legal_name', 'Farmacia del Norte');
    }

    // ── GET /v1/suppliers/{id} ────────────────────────────────────────────────

    public function test_show_returns_200_with_supplier_data(): void
    {
        $supplier = $this->createSupplier();

        $response = $this->actingAsUser()->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $supplier->id)
            ->assertJsonPath('data.legal_name', 'Distribuidora Farmacéutica S.A. de C.V.')
            ->assertJsonPath('data.type', 'MORAL')
            ->assertJsonPath('data.type_label', 'Persona Moral')
            ->assertJsonPath('data.rfc', 'ABC123456XYZ');
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/v1/suppliers/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    // ── POST /v1/suppliers ─────────────────────────────────────────────────────

    public function test_store_returns_201_with_created_supplier(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.legal_name', 'Nueva Distribuidora S.A. de C.V.')
            ->assertJsonPath('data.type', 'MORAL')
            ->assertJsonPath('data.rfc', 'XYZ987654ABC')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_store_creates_supplier_without_rfc(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload([
            'type' => 'FISICA',
            'rfc'  => null,
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.rfc', null)
            ->assertJsonPath('data.type', 'FISICA');
    }

    public function test_store_creates_moral_supplier_with_phone_and_email(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload([
            'phone' => '2221234567',
            'email' => 'contacto@proveedor.mx',
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.phone', '2221234567')
            ->assertJsonPath('data.email', 'contacto@proveedor.mx');
    }

    public function test_store_returns_409_when_rfc_already_exists(): void
    {
        $this->createSupplier(['rfc' => 'XYZ987654ABC']);

        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload());

        $response->assertStatus(409);
    }

    public function test_store_returns_422_when_type_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['type']);

        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_returns_422_when_legal_name_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['legal_name']);

        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['legal_name']);
    }

    public function test_store_returns_422_when_address_state_is_missing(): void
    {
        $payload                        = $this->validPayload();
        $payload['address']['state']    = '';

        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $payload);

        $response->assertStatus(422);
    }

    public function test_store_returns_422_when_postal_code_has_wrong_format(): void
    {
        $payload                             = $this->validPayload();
        $payload['address']['postal_code']   = '7200X';

        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address.postal_code']);
    }

    public function test_store_returns_422_when_rfc_length_does_not_match_type(): void
    {
        // MORAL needs 12 chars, giving 13
        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload([
            'type' => 'MORAL',
            'rfc'  => 'ABCD12345678X', // 13 chars
        ]));

        $response->assertStatus(422);
    }

    public function test_store_allows_multiple_suppliers_with_null_rfc(): void
    {
        $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload(['type' => 'FISICA', 'rfc' => null]));
        $response = $this->actingAsUser()->postJson('/api/v1/suppliers', $this->validPayload([
            'type'       => 'FISICA',
            'rfc'        => null,
            'legal_name' => 'Otro Proveedor Extranjero',
        ]));

        $response->assertStatus(201);
    }

    // ── PUT /v1/suppliers/{id} ─────────────────────────────────────────────────

    public function test_update_returns_200_with_updated_data(): void
    {
        $supplier = $this->createSupplier();

        $response = $this->actingAsUser()->putJson("/api/v1/suppliers/{$supplier->id}", [
            'legal_name' => 'Razón Social Actualizada S.A.',
            'trade_name' => 'RSA',
            'address'    => [
                'street'       => 'Calle Nueva',
                'ext_number'   => '99',
                'neighborhood' => 'Sur',
                'municipality' => 'Puebla',
                'state'        => 'Puebla',
                'postal_code'  => '72020',
                'country'      => 'MX',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.legal_name', 'Razón Social Actualizada S.A.')
            ->assertJsonPath('data.trade_name', 'RSA');
    }

    public function test_update_does_not_change_type_or_rfc(): void
    {
        $supplier = $this->createSupplier(['type' => 'MORAL', 'rfc' => 'ABC123456XYZ']);

        $this->actingAsUser()->putJson("/api/v1/suppliers/{$supplier->id}", [
            'legal_name' => 'Nombre Nuevo S.A.',
            'address'    => [
                'street'       => 'Calle',
                'ext_number'   => '1',
                'neighborhood' => 'Col',
                'municipality' => 'Puebla',
                'state'        => 'Puebla',
                'postal_code'  => '72000',
                'country'      => 'MX',
            ],
        ]);

        $supplier->refresh();
        self::assertSame('MORAL', $supplier->type);
        self::assertSame('ABC123456XYZ', $supplier->rfc);
    }

    public function test_update_returns_404_when_supplier_not_found(): void
    {
        $response = $this->actingAsUser()->putJson('/api/v1/suppliers/00000000-0000-4000-8000-000000000000', [
            'legal_name' => 'Nombre',
            'address'    => [
                'street'       => 'Calle',
                'ext_number'   => '1',
                'neighborhood' => 'Col',
                'municipality' => 'Mun',
                'state'        => 'Puebla',
                'postal_code'  => '72000',
            ],
        ]);

        $response->assertStatus(404);
    }

    public function test_update_returns_422_when_legal_name_is_empty(): void
    {
        $supplier = $this->createSupplier();

        $response = $this->actingAsUser()->putJson("/api/v1/suppliers/{$supplier->id}", [
            'legal_name' => '',
            'address'    => [
                'street'       => 'Calle',
                'ext_number'   => '1',
                'neighborhood' => 'Col',
                'municipality' => 'Mun',
                'state'        => 'Puebla',
                'postal_code'  => '72000',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['legal_name']);
    }

    // ── DELETE /v1/suppliers/{id} ──────────────────────────────────────────────

    public function test_destroy_returns_204_and_deactivates_supplier(): void
    {
        $supplier = $this->createSupplier();

        $response = $this->actingAsUser()->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(204);

        $supplier->refresh();
        self::assertFalse($supplier->is_active);
    }

    public function test_destroy_returns_404_when_not_found(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/v1/suppliers/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404);
    }

    public function test_destroy_returns_409_when_already_inactive(): void
    {
        $supplier = $this->createSupplier(['is_active' => false]);

        $response = $this->actingAsUser()->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(409);
    }

    // ── Auth / RBAC ────────────────────────────────────────────────────────────

    public function test_all_endpoints_return_401_without_authentication(): void
    {
        $id = '00000000-0000-4000-8000-000000000000';

        $this->getJson('/api/v1/suppliers')->assertStatus(401);
        $this->getJson("/api/v1/suppliers/{$id}")->assertStatus(401);
        $this->postJson('/api/v1/suppliers')->assertStatus(401);
        $this->putJson("/api/v1/suppliers/{$id}")->assertStatus(401);
        $this->deleteJson("/api/v1/suppliers/{$id}")->assertStatus(401);
    }

    public function test_all_endpoints_return_403_without_suppliers_manage_permission(): void
    {
        $userWithoutPermission = EloquentUser::factory()->create([
            'status'               => 'ACTIVE',
            'email_verified_at'    => now(),
            'must_change_password' => false,
        ]);

        $id = '00000000-0000-4000-8000-000000000000';

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/suppliers')->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/suppliers', [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/suppliers/{$id}", [])->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$id}")->assertStatus(403);
    }
}
