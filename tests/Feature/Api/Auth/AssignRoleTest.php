<?php

// ── ARCHIVO: tests/Feature/Api/Auth/AssignRoleTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AssignRoleTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(string $email, string $password): array
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'client_type' => 'WEB',
        ]);

        return $response->json('data') ?? [];
    }

    private function createUserWithRole(string $email, string $roleName, int $level): EloquentUser
    {
        $user = EloquentUser::factory()->create([
            'email' => $email,
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ]);

        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'api'],
            ['label' => ucfirst($roleName), 'hierarchy_level' => $level, 'branch_scoped' => false],
        );
        $user->assignRole($role);

        return $user;
    }

    public function test_manager_can_assign_lower_level_role_to_user(): void
    {
        $manager = $this->createUserWithRole('manager@e.com', 'branch-manager', 4);
        $target = EloquentUser::factory()->create([
            'email' => 'target@e.com',
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ]);
        $pharmacistRole = Role::firstOrCreate(
            ['name' => 'pharmacist', 'guard_name' => 'api'],
            ['label' => 'Pharmacist', 'hierarchy_level' => 6, 'branch_scoped' => false],
        );

        $tokens = $this->loginAs('manager@e.com', 'Secret123!');

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/v1/auth/users/'.$target->id.'/roles', [
                'role_id' => $pharmacistRole->id,
            ]);

        $response->assertStatus(200);
    }

    public function test_returns_403_when_actor_tries_to_assign_higher_level_role(): void
    {
        $pharmacist = $this->createUserWithRole('pharmacist@e.com', 'pharmacist', 6);
        $target = EloquentUser::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ]);
        $managerRole = Role::firstOrCreate(
            ['name' => 'branch-manager', 'guard_name' => 'api'],
            ['label' => 'Branch Manager', 'hierarchy_level' => 4, 'branch_scoped' => false],
        );

        $tokens = $this->loginAs('pharmacist@e.com', 'Secret123!');

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/v1/auth/users/'.$target->id.'/roles', [
                'role_id' => $managerRole->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_returns_401_when_not_authenticated(): void
    {
        $target = EloquentUser::factory()->create();
        $role = Role::firstOrCreate(
            ['name' => 'pharmacist', 'guard_name' => 'api'],
            ['label' => 'Pharmacist', 'hierarchy_level' => 6, 'branch_scoped' => false],
        );

        $response = $this->postJson('/api/v1/auth/users/'.$target->id.'/roles', [
            'role_id' => $role->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_409_when_role_is_already_assigned(): void
    {
        $manager = $this->createUserWithRole('manager@e.com', 'branch-manager', 4);
        $target = $this->createUserWithRole('target@e.com', 'pharmacist', 6);
        $pharmacistRole = Role::where('name', 'pharmacist')->first();

        $tokens = $this->loginAs('manager@e.com', 'Secret123!');

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/v1/auth/users/'.$target->id.'/roles', [
                'role_id' => $pharmacistRole->id,
            ]);

        $response->assertStatus(409);
    }

    public function test_returns_422_when_role_id_is_missing(): void
    {
        $manager = $this->createUserWithRole('manager@e.com', 'branch-manager', 4);
        $target = EloquentUser::factory()->create();

        $tokens = $this->loginAs('manager@e.com', 'Secret123!');

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/v1/auth/users/'.$target->id.'/roles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role_id']);
    }
}
