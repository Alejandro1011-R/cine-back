<?php

declare(strict_types=1);

namespace Tests\Feature\Cinema;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CinemaTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(string $ci, string $rol): Usuario
    {
        Cliente::create(['ci' => $ci, 'correo' => "{$ci}@staff.test"]);

        return Usuario::create([
            'ci' => $ci,
            'nombre_s' => 'Staff',
            'contrasena' => 'x',
            'codigo' => $ci,
            'rol' => $rol,
            'puntos' => 0,
        ]);
    }

    public function test_crud_salas(): void
    {
        $admin = $this->createStaff('99999999999', 'SuperAdmin');

        $r = $this->actingAs($admin, 'sanctum')->postJson('/api/salas', ['capacidad' => 100]);
        $r->assertStatus(201);
        $id = $r->json('id_s');
        $this->actingAs($admin, 'sanctum')->getJson('/api/salas')->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson("/api/salas/{$id}")->assertOk();
        $this->actingAs($admin, 'sanctum')->putJson("/api/salas/{$id}", ['capacidad' => 150])->assertStatus(204);
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/salas/{$id}")->assertStatus(204);
    }

    public function test_crud_butacas(): void
    {
        $admin = $this->createStaff('99999999999', 'SuperAdmin');
        $sala = Sala::create(['capacidad' => 50]);
        $r = $this->actingAs($admin, 'sanctum')->postJson('/api/butacas', ['id_s' => $sala->id_s]);
        $r->assertStatus(201);
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/butacas/{$r->json('id_b')}")->assertStatus(204);
    }

    public function test_crear_sesion(): void
    {
        $admin = $this->createStaff('99999999999', 'SuperAdmin');
        $p = Pelicula::create(['titulo' => 'Test', 'duracion' => 120]);
        $s = Sala::create(['capacidad' => 100]);
        $r = $this->actingAs($admin, 'sanctum')->postJson('/api/sesiones', ['id_p' => $p->id_p, 'id_s' => $s->id_s, 'fecha' => '2025-06-15 20:00:00']);
        $r->assertStatus(201);
        $this->assertDatabaseHas('sesiones', ['id_p' => $p->id_p, 'id_s' => $s->id_s]);
    }

    public function test_sesion_detecta_solapamiento(): void
    {
        $admin = $this->createStaff('99999999999', 'SuperAdmin');
        $p = Pelicula::create(['titulo' => 'Film', 'duracion' => 120]);
        $s = Sala::create(['capacidad' => 100]);
        $this->actingAs($admin, 'sanctum')->postJson('/api/sesiones', ['id_p' => $p->id_p, 'id_s' => $s->id_s, 'fecha' => '2025-06-15 20:00:00'])->assertStatus(201);
        $this->actingAs($admin, 'sanctum')->postJson('/api/sesiones', ['id_p' => $p->id_p, 'id_s' => $s->id_s, 'fecha' => '2025-06-15 21:00:00'])->assertStatus(409);
    }

    public function test_sesion_ok_diferente_sala(): void
    {
        $admin = $this->createStaff('99999999999', 'SuperAdmin');
        $p = Pelicula::create(['titulo' => 'Film', 'duracion' => 120]);
        $s1 = Sala::create(['capacidad' => 100]);
        $s2 = Sala::create(['capacidad' => 80]);
        $this->actingAs($admin, 'sanctum')->postJson('/api/sesiones', ['id_p' => $p->id_p, 'id_s' => $s1->id_s, 'fecha' => '2025-06-15 20:00:00'])->assertStatus(201);
        $this->actingAs($admin, 'sanctum')->postJson('/api/sesiones', ['id_p' => $p->id_p, 'id_s' => $s2->id_s, 'fecha' => '2025-06-15 20:00:00'])->assertStatus(201);
    }

    public function test_admin_puede_pertenecer_a_varios_cines(): void
    {
        $admin = $this->createStaff('12345678901', 'Admin');
        $cineA = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);
        $cineB = Cine::create(['nombre' => 'Norte', 'direccion' => 'Avenida 2']);
        $cineNoAsignado = Cine::create(['nombre' => 'Sur', 'direccion' => 'Avenida 3']);

        $admin->cines()->attach([$cineA->id_c, $cineB->id_c]);

        $this->assertTrue($admin->belongsToCine((int) $cineA->id_c));
        $this->assertTrue($admin->belongsToCine((int) $cineB->id_c));
        $this->assertFalse($admin->belongsToCine((int) $cineNoAsignado->id_c));

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/cines')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/cines/{$cineB->id_c}")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/cines/{$cineNoAsignado->id_c}")
            ->assertForbidden();
    }

    public function test_super_admin_asigna_y_desasigna_staff_a_cine(): void
    {
        $superAdmin = $this->createStaff('99999999999', 'SuperAdmin');
        $taquillero = $this->createStaff('11111111111', 'Taquillero');
        $cine = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/cines/{$cine->id_c}/staff", ['ci' => $taquillero->ci])
            ->assertOk();

        $this->assertDatabaseHas('cine_usuario', [
            'id_c' => $cine->id_c,
            'ci' => $taquillero->ci,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_ci' => $superAdmin->ci,
            'action' => 'cinema.staff.assigned',
            'auditable_type' => Cine::class,
            'auditable_id' => (string) $cine->id_c,
        ]);

        $this->actingAs($superAdmin, 'sanctum')
            ->deleteJson("/api/cines/{$cine->id_c}/staff/{$taquillero->ci}")
            ->assertNoContent();

        $this->assertDatabaseMissing('cine_usuario', [
            'id_c' => $cine->id_c,
            'ci' => $taquillero->ci,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_ci' => $superAdmin->ci,
            'action' => 'cinema.staff.detached',
            'auditable_type' => Cine::class,
            'auditable_id' => (string) $cine->id_c,
        ]);
    }

    public function test_admin_no_puede_asignar_staff_a_cine(): void
    {
        $admin = $this->createStaff('99999999999', 'Admin');
        $taquillero = $this->createStaff('11111111111', 'Taquillero');
        $cine = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/cines/{$cine->id_c}/staff", ['ci' => $taquillero->ci])
            ->assertForbidden();
    }

    public function test_no_asigna_clientes_como_staff_de_cine(): void
    {
        $superAdmin = $this->createStaff('99999999999', 'SuperAdmin');
        $cliente = $this->createStaff('11111111111', 'Cliente');
        $cine = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/cines/{$cine->id_c}/staff", ['ci' => $cliente->ci])
            ->assertUnprocessable();
    }

    public function test_creacion_de_sesion_queda_auditada(): void
    {
        $admin = $this->createStaff('99999999999', 'SuperAdmin');
        $p = Pelicula::create(['titulo' => 'Test', 'duracion' => 120]);
        $s = Sala::create(['capacidad' => 100]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/sesiones', ['id_p' => $p->id_p, 'id_s' => $s->id_s, 'fecha' => '2025-06-15 20:00:00'])
            ->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'actor_ci' => $admin->ci,
            'action' => 'cinema.session.created',
            'auditable_type' => \App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion::class,
        ]);
    }
}
