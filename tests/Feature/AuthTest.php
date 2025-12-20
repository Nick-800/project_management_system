<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed');
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $payload = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/auth/register', $payload);
        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_login_returns_token_and_me_works(): void
    {
        $user = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', ['email' => 'bob@example.com', 'password' => 'password']);
        $login->assertOk();
        $token = $login->json('token');

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me');
        $me->assertOk()->assertJson(['id' => $user->id, 'email' => 'bob@example.com']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create(['name' => 'Carl', 'email' => 'carl@example.com', 'password' => 'password']);

        $resp = $this->postJson('/api/auth/login', ['email' => 'carl@example.com', 'password' => 'wrong']);
        $resp->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Dana', 'email' => 'dana@example.com', 'password' => 'password',
        ])->assertCreated();
        $token = $register->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->app['auth']->forgetGuards();
        
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
