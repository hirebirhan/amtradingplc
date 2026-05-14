<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'     => 'test@example.com',
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'user' => ['id', 'name', 'email', 'is_active'],
            ]);

        $this->assertSame('Bearer', $response->json('token_type'));
    }

    public function test_login_fails_for_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_rejects_inactive_user(): void
    {
        User::factory()->create([
            'email'     => 'inactive@example.com',
            'password'  => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
    }

    public function test_me_returns_403_for_inactive_token_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertForbidden()
            ->assertJsonPath('message', 'Account is inactive.');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logged out.');
    }

    public function test_profile_update_changes_name(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/me/profile', [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }
}
