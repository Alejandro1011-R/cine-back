<?php

declare(strict_types=1);

namespace Tests\Feature\Ticketing;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Descuento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompraTest extends TestCase
{
    use RefreshDatabase;

    private function setupSesion(): array
    {
        $p  = Pelicula::create(['titulo' => 'Film', 'duracion' => 120]);
        $cine = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);
        $s  = Sala::create(['id_c' => $cine->id_c, 'capacidad' => 50]);
        $b1 = Butaca::create(['id_s' => $s->id_s]);
        $b2 = Butaca::create(['id_s' => $s->id_s]);
        $sesion = new Sesion();
        $sesion->id_p  = $p->id_p;
        $sesion->id_s  = $s->id_s;
        $sesion->fecha = '2025-12-25 20:00:00';
        $sesion->save();
        return ['p' => $p, 's' => $s, 'b1' => $b1, 'b2' => $b2, 'cine' => $cine];
    }

    private function setupUser(string $ci = '12345678901', string $rol = 'Socio', int $puntos = 100): Usuario
    {
        Cliente::create(['ci' => $ci, 'correo' => 'u@t.com']);
        return Usuario::create([
            'ci'        => $ci,
            'nombre_s'  => 'User',
            'contrasena'=> 'x',
            'codigo'    => substr($ci, 0, 11),
            'rol'       => $rol,
            'puntos'    => $puntos,
        ]);
    }

    public function test_compra_by_tarjeta(): void
    {
        $d = $this->setupSesion();
        $u = $this->setupUser();

        $r = $this->actingAs($u, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p'          => $d['p']->id_p,
            'id_s'          => $d['s']->id_s,
            'fecha'         => '2025-12-25 20:00:00',
            'ci'            => $u->ci,
            'cantidad'      => 25.50,
            'codigo_t'      => '123456789012345678',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids'    => [$d['b1']->id_b, $d['b2']->id_b],
        ]);

        $r->assertStatus(201);
        $this->assertDatabaseHas('compras', ['ci' => $u->ci, 'tipo' => 'Tarjeta']);
        $this->assertDatabaseHas('web_payments', [
            'cantidad' => 25.50,
            'codigo_t' => null,
            'gateway_status' => 'approved',
            'card_last_four' => '5678',
        ]);
        $u->refresh();
        $this->assertEquals(110, $u->puntos); // 100 + 5*2
    }

    public function test_no_permite_reservar_butaca_ya_ocupada(): void
    {
        $d = $this->setupSesion();
        $u1 = $this->setupUser('12345678901', 'Socio', 100);
        $u2 = $this->setupUser('22222222222', 'Socio', 100);

        $this->actingAs($u1, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $u1->ci,
            'cantidad' => 25.50,
            'codigo_t' => '123456789012345678',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ])->assertStatus(201);

        $this->actingAs($u2, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $u2->ci,
            'cantidad' => 25.50,
            'codigo_t' => '999999999999999999',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ])->assertStatus(409);
    }

    public function test_compra_by_taquilla_efectivo(): void
    {
        $d          = $this->setupSesion();
        $taquillero = $this->setupUser('99999999999', 'Taquillero', 0);
        $taquillero->cines()->attach($d['cine']->id_c);

        $r = $this->actingAs($taquillero, 'sanctum')->postJson('/api/compras/taquilla', [
            'id_p'            => $d['p']->id_p,
            'id_s'            => $d['s']->id_s,
            'fecha'           => '2025-12-25 20:00:00',
            'ci_taquillero'   => $taquillero->ci,
            'ci'              => '11111111111',
            'cantidad'        => 15.00,
            'correo'          => 'cli@t.com',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids'      => [$d['b1']->id_b],
        ]);

        $r->assertStatus(201);
        $this->assertDatabaseHas('compras', ['tipo' => 'Efectivo']);
        $this->assertDatabaseHas('efectivos', ['cantidad_e' => 15.00]);
        $this->assertDatabaseHas('clientes', ['ci' => '11111111111']);
    }

    public function test_compra_by_puntos(): void
    {
        $d = $this->setupSesion();
        $u = $this->setupUser('12345678901', 'Socio', 50);

        $r = $this->actingAs($u, 'sanctum')->postJson('/api/compras/puntos', [
            'id_p'            => $d['p']->id_p,
            'id_s'            => $d['s']->id_s,
            'fecha'           => '2025-12-25 20:00:00',
            'ci'              => $u->ci,
            'cantidad'        => 20,
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids'      => [$d['b1']->id_b],
        ]);

        $r->assertStatus(201);
        $u->refresh();
        $this->assertEquals(30, $u->puntos); // 50 - 20
    }

    public function test_compra_puntos_insuficientes(): void
    {
        $d = $this->setupSesion();
        $u = $this->setupUser('12345678901', 'Socio', 5);

        $r = $this->actingAs($u, 'sanctum')->postJson('/api/compras/puntos', [
            'id_p'            => $d['p']->id_p,
            'id_s'            => $d['s']->id_s,
            'fecha'           => '2025-12-25 20:00:00',
            'ci'              => $u->ci,
            'cantidad'        => 100,
            'fecha_de_compra' => now()->toDateTimeString(),
            'butaca_ids'      => [$d['b1']->id_b],
        ]);

        $r->assertStatus(400);
    }

    public function test_confiabilidad_bloquea_descuentos(): void
    {
        $d    = $this->setupSesion();
        Cliente::create(['ci' => '12345678901', 'correo' => 'u@t.com', 'confiabilidad' => false]);
        $u    = Usuario::create(['ci' => '12345678901', 'nombre_s' => 'Bad', 'contrasena' => 'x', 'codigo' => '12345678901', 'rol' => 'Socio']);
        $desc = Descuento::create(['nombre_d' => 'Promo', 'porciento' => 10.0]);

        $r = $this->actingAs($u, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p'            => $d['p']->id_p,
            'id_s'            => $d['s']->id_s,
            'fecha'           => '2025-12-25 20:00:00',
            'ci'              => '12345678901',
            'cantidad'        => 20,
            'codigo_t'        => '123456789012345678',
            'fecha_de_compra' => now()->toDateTimeString(),
            'butaca_ids'      => [$d['b1']->id_b],
            'descuento_ids'   => [$desc->id_d],
        ]);

        $r->assertStatus(400);
    }

    public function test_taquillero_no_valido_rechaza_compra(): void
    {
        $d      = $this->setupSesion();
        $cliente = $this->setupUser('99999999999', 'Cliente', 0);

        // Un Cliente intenta operar como taquillero → la Action lo rechaza (400)
        $r = $this->actingAs($cliente, 'sanctum')->postJson('/api/compras/taquilla', [
            'id_p'            => $d['p']->id_p,
            'id_s'            => $d['s']->id_s,
            'fecha'           => '2025-12-25 20:00:00',
            'ci_taquillero'   => $cliente->ci,
            'ci'              => '11111111111',
            'cantidad'        => 10,
            'correo'          => 'x@x.com',
            'fecha_de_compra' => now()->toDateTimeString(),
            'butaca_ids'      => [$d['b1']->id_b],
        ]);

        $r->assertStatus(400);
    }

    public function test_no_permite_comprar_con_ci_de_otro_usuario(): void
    {
        $d = $this->setupSesion();
        $u = $this->setupUser('12345678901', 'Socio', 100);
        $this->setupUser('22222222222', 'Socio', 100);

        $this->actingAs($u, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => '22222222222',
            'cantidad' => 25.50,
            'codigo_t' => '123456789012345678',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ])->assertForbidden();
    }

    public function test_no_permite_comprar_butaca_de_otra_sala(): void
    {
        $d = $this->setupSesion();
        $u = $this->setupUser('12345678901', 'Socio', 100);
        $otraSala = Sala::create(['id_c' => $d['cine']->id_c, 'capacidad' => 10]);
        $butacaAjena = Butaca::create(['id_s' => $otraSala->id_s]);

        $this->actingAs($u, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $u->ci,
            'cantidad' => 25.50,
            'codigo_t' => '123456789012345678',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$butacaAjena->id_b],
        ])->assertStatus(400);
    }

    public function test_cliente_no_autenticado_es_rechazado(): void
    {
        // Sin actingAs → 401 Unauthenticated
        $r = $this->postJson('/api/compras/tarjeta', ['ci' => '11111111111']);
        $r->assertStatus(401);
    }

    public function test_cliente_solo_ve_sus_compras(): void
    {
        $u = $this->setupUser('12345678901', 'Socio', 0);

        // Sin compras, el índice devuelve array vacío para este usuario
        $r = $this->actingAs($u, 'sanctum')->getJson('/api/compras');

        $r->assertStatus(200);
        $r->assertJsonCount(0, 'data');
    }
}
