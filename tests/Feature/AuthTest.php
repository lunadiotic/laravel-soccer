<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('api/auth/register', [
            'name' => 'John Doe',
            'email' => 'a@b.c',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token']
            ])
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'a@b.c'
        ]);
    }

    public function test_register_requires_all_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'email', 'password']]);
    }

    public function test_register_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'admin@test.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Admin Dua',
            'email'                 => 'admin@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'admin@test.com']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'salah123',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_logout(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
