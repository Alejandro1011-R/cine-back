<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Infrastructure\Hashing\Sha256PasswordHasher;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_exitoso_con_bcrypt(): void
    {
        $hasher = $this->app->make(Sha256PasswordHasher::class);

        Cliente::create(['ci' => '12345678901']);
        Usuario::create([
            'ci' => '12345678901',
            'nombre_s' => 'Juan',
            'contrasena' => $hasher->make('password123'),
            'codigo' => 'ABC12345678',
            'rol' => 'Admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'ci' => '12345678901',
            'contrasena' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['usuario', 'token']);
        $response->assertJsonFragment(['ci' => '12345678901']);
    }

    public function test_login_exitoso_con_hash_sha256(): void
    {
        $sha256Hash = hash('sha256', 'password123');

        Cliente::create(['ci' => '12345678901']);
        Usuario::create([
            'ci' => '12345678901',
            'nombre_s' => 'Hash User',
            'contrasena' => $sha256Hash,
            'codigo' => 'HSH12345678',
            'rol' => 'Cliente',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'ci' => '12345678901',
            'contrasena' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);

        // Verify password was re-hashed to bcrypt
        $usuario = Usuario::find('12345678901');
        $this->assertTrue(str_starts_with($usuario->contrasena, '$2y$'));
    }

    public function test_login_fallido_credenciales_invalidas(): void
    {
        $hasher = $this->app->make(Sha256PasswordHasher::class);

        Cliente::create(['ci' => '12345678901']);
        Usuario::create([
            'ci' => '12345678901',
            'nombre_s' => 'Juan',
            'contrasena' => $hasher->make('password123'),
            'codigo' => 'ABC12345678',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'ci' => '12345678901',
            'contrasena' => 'wrongpassword',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_fallido_usuario_no_existe(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'ci' => '99999999999',
            'contrasena' => 'anything',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_tiene_rate_limit_especifico(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'ci' => '99999999999',
                'contrasena' => 'wrongpassword',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', [
            'ci' => '99999999999',
            'contrasena' => 'wrongpassword',
        ])->assertStatus(429);
    }

    public function test_logout_revoca_token_actual(): void
    {
        $hasher = $this->app->make(Sha256PasswordHasher::class);

        Cliente::create(['ci' => '12345678901']);
        $usuario = Usuario::create([
            'ci' => '12345678901',
            'nombre_s' => 'Juan',
            'contrasena' => $hasher->make('password123'),
            'codigo' => 'ABC12345678',
            'rol' => 'Cliente',
        ]);

        $token = $usuario->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }
}
