<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    private function createUsuario(string $ci, string $rol = 'Cliente', string $nombre = 'User'): Usuario
    {
        Cliente::create(['ci' => $ci, 'correo' => "{$ci}@test.com"]);

        return Usuario::create([
            'ci' => $ci,
            'nombre_s' => $nombre,
            'contrasena' => 'x',
            'codigo' => $ci,
            'rol' => $rol,
            'puntos' => 0,
        ]);
    }

    public function test_puede_registrar_usuario(): void
    {
        $response = $this->postJson('/api/usuarios', [
            'ci' => '12345678901',
            'nombre_s' => 'Juan',
            'apellidos' => 'Pérez',
            'correo' => 'juan@test.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['ci' => '12345678901', 'nombre_s' => 'Juan']);

        $this->assertDatabaseHas('clientes', ['ci' => '12345678901']);
        $this->assertDatabaseHas('usuarios', ['ci' => '12345678901', 'nombre_s' => 'Juan']);
    }

    public function test_usuario_genera_codigo_unico(): void
    {
        $this->postJson('/api/usuarios', [
            'ci' => '12345678901',
            'nombre_s' => 'Juan',
            'apellidos' => 'Pérez',
            'correo' => 'juan@test.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
        ]);

        $usuario = Usuario::find('12345678901');
        $this->assertNotNull($usuario->codigo);
        $this->assertEquals(11, strlen($usuario->codigo));
    }

    public function test_no_puede_registrar_usuario_duplicado(): void
    {
        Cliente::create(['ci' => '12345678901', 'correo' => 'a@b.com']);
        Usuario::create([
            'ci' => '12345678901',
            'nombre_s' => 'Existente',
            'contrasena' => 'hash',
            'codigo' => 'ABC12345678',
        ]);

        $response = $this->postJson('/api/usuarios', [
            'ci' => '12345678901',
            'nombre_s' => 'Otro',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
        ]);

        $response->assertStatus(409);
    }

    public function test_usuario_anonimo_no_puede_listar_usuarios(): void
    {
        $this->createUsuario('11111111111', 'Cliente', 'Ana');

        $response = $this->getJson('/api/usuarios');
        $response->assertUnauthorized();
    }

    public function test_super_admin_puede_listar_usuarios_y_filtrar_por_rol(): void
    {
        $superAdmin = $this->createUsuario('99999999999', 'SuperAdmin', 'Root');
        $this->createUsuario('11111111111', 'Taquillero', 'Ana');
        $this->createUsuario('22222222222', 'Taquillero', 'Luis');
        $this->createUsuario('33333333333', 'Cliente', 'Cliente');

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/usuarios?rol=Taquillero');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['nombre_s' => 'Ana']);
        $response->assertJsonMissing(['nombre_s' => 'Cliente']);
    }

    public function test_admin_solo_lista_taquilleros_de_sus_cines(): void
    {
        $admin = $this->createUsuario('99999999999', 'Admin', 'Admin');
        $taquilleroAsignado = $this->createUsuario('11111111111', 'Taquillero', 'Taquilla Centro');
        $taquilleroAjeno = $this->createUsuario('22222222222', 'Taquillero', 'Taquilla Sur');
        $this->createUsuario('33333333333', 'Cliente', 'Cliente');
        $cineCentro = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);
        $cineSur = Cine::create(['nombre' => 'Sur', 'direccion' => 'Avenida 2']);

        $admin->cines()->attach($cineCentro->id_c);
        $taquilleroAsignado->cines()->attach($cineCentro->id_c);
        $taquilleroAjeno->cines()->attach($cineSur->id_c);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/usuarios?rol=Taquillero');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['nombre_s' => 'Taquilla Centro']);
        $response->assertJsonMissing(['nombre_s' => 'Taquilla Sur']);
        $response->assertJsonMissing(['nombre_s' => 'Cliente']);
    }

    public function test_cliente_no_puede_listar_usuarios(): void
    {
        $cliente = $this->createUsuario('11111111111', 'Cliente', 'Cliente');

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/usuarios')
            ->assertForbidden();
    }

    public function test_obtener_usuario_por_ci(): void
    {
        $usuario = $this->createUsuario('11111111111', 'Cliente', 'Ana');

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/usuarios/11111111111');
        $response->assertOk();
        $response->assertJsonFragment(['nombre_s' => 'Ana']);
    }

    public function test_admin_no_puede_ver_taquillero_de_otro_cine(): void
    {
        $admin = $this->createUsuario('99999999999', 'Admin', 'Admin');
        $taquilleroAjeno = $this->createUsuario('22222222222', 'Taquillero', 'Taquilla Sur');
        $cineCentro = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);
        $cineSur = Cine::create(['nombre' => 'Sur', 'direccion' => 'Avenida 2']);

        $admin->cines()->attach($cineCentro->id_c);
        $taquilleroAjeno->cines()->attach($cineSur->id_c);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/usuarios/22222222222')
            ->assertForbidden();
    }

    public function test_usuario_no_encontrado(): void
    {
        $superAdmin = $this->createUsuario('11111111111', 'SuperAdmin', 'Root');

        $response = $this->actingAs($superAdmin, 'sanctum')->getJson('/api/usuarios/99999999999');
        $response->assertNotFound();
    }

    public function test_eliminar_usuario(): void
    {
        $superAdmin = $this->createUsuario('99999999999', 'SuperAdmin', 'Root');
        $this->createUsuario('11111111111', 'Cliente', 'Ana');

        $response = $this->actingAs($superAdmin, 'sanctum')->deleteJson('/api/usuarios/11111111111');
        $response->assertStatus(204);
        $this->assertSoftDeleted('usuarios', ['ci' => '11111111111']);
    }

    public function test_solo_super_admin_puede_cambiar_rol_usuario(): void
    {
        $superAdmin = $this->createUsuario('99999999999', 'SuperAdmin', 'Root');
        $this->createUsuario('11111111111', 'Cliente', 'Ana');

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->putJson('/api/usuarios/11111111111/change-role/Admin');
        $response->assertStatus(204);

        $usuario = Usuario::find('11111111111');
        $this->assertEquals('Admin', $usuario->rol);
        $this->assertDatabaseHas('role_change_audits', [
            'actor_ci' => $superAdmin->ci,
            'target_ci' => '11111111111',
            'old_role' => 'Cliente',
            'new_role' => 'Admin',
        ]);
    }

    public function test_rechaza_contrasena_debil_en_registro(): void
    {
        $this->postJson('/api/usuarios', [
            'ci' => '12345678901',
            'nombre_s' => 'Juan',
            'correo' => 'juan@test.com',
            'contrasena' => 'x',
            'contrasena_confirmation' => 'x',
        ])->assertUnprocessable();
    }

    public function test_admin_no_puede_cambiar_rol_usuario(): void
    {
        $admin = $this->createUsuario('99999999999', 'Admin', 'Admin');
        $this->createUsuario('11111111111', 'Cliente', 'Ana');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/usuarios/11111111111/change-role/Admin')
            ->assertForbidden();
    }

    public function test_promover_a_socio(): void
    {
        $usuario = $this->createUsuario('11111111111', 'Cliente', 'Ana');

        $response = $this->actingAs($usuario, 'sanctum')
            ->putJson('/api/usuarios/11111111111/promote-socio');
        $response->assertStatus(204);

        $usuario = Usuario::find('11111111111');
        $this->assertEquals('Socio', $usuario->rol);
    }
}
