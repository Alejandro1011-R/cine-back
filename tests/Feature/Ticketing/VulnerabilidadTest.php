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
use App\Modules\Ticketing\Domain\Contracts\SeatAvailabilityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class VulnerabilidadTest extends TestCase
{
    use RefreshDatabase;

    private function setupSesion(): array
    {
        $p  = Pelicula::create(['titulo' => 'Film', 'duracion' => 120]);
        $cine = Cine::create(['nombre' => 'Centro', 'direccion' => 'Avenida 1']);
        $s  = Sala::create(['id_c' => $cine->id_c, 'capacidad' => 50]);
        $b1 = Butaca::create(['id_s' => $s->id_s]);
        $sesion = new Sesion();
        $sesion->id_p  = $p->id_p;
        $sesion->id_s  = $s->id_s;
        $sesion->fecha = '2025-12-25 20:00:00';
        $sesion->save();
        return ['p' => $p, 's' => $s, 'b1' => $b1];
    }

    private function setupUser(string $ci): Usuario
    {
        Cliente::create(['ci' => $ci, 'correo' => "{$ci}@t.com"]);
        return Usuario::create([
            'ci'        => $ci,
            'nombre_s'  => 'User',
            'contrasena'=> 'x',
            'codigo'    => substr($ci, 0, 11),
            'rol'       => 'Socio',
            'puntos'    => 100,
        ]);
    }

    public function test_vulnerabilidad_doble_reserva_por_falla_en_base_de_datos(): void
    {
        $d = $this->setupSesion();
        $user1 = $this->setupUser('11111111111');
        $user2 = $this->setupUser('22222222222');

        $this->mock(SeatAvailabilityChecker::class, function ($mock) {
            $mock->shouldReceive('hasReservedSeats')->andReturn(false);
        });

        $response1 = $this->actingAs($user1, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $user1->ci,
            'cantidad' => 10,
            'codigo_t' => '123456789012345678',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ]);

        $response2 = $this->actingAs($user2, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $user2->ci,
            'cantidad' => 10,
            'codigo_t' => '999999999999999999',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(409);

        $reservas = DB::table('butacas_reservadas')->where('id_b', $d['b1']->id_b)->get();

        $this->assertCount(1, $reservas);
    }

    public function test_no_guarda_tarjeta_en_texto_plano_y_registra_respuesta_pasarela(): void
    {
        $d = $this->setupSesion();
        $user = $this->setupUser('11111111111');
        
        $tarjetaPeligrosa = '4532123412341234';

        $this->actingAs($user, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $user->ci,
            'cantidad' => 10,
            'codigo_t' => $tarjetaPeligrosa,
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ])->assertStatus(201)
            ->assertJsonPath('pago.web_payment.gateway_status', 'approved')
            ->assertJsonPath('pago.web_payment.card_last_four', '1234');

        $this->assertFalse(Schema::hasTable('tarjetas'));
        $this->assertDatabaseMissing('web_payments', ['codigo_t' => $tarjetaPeligrosa]);
        $this->assertDatabaseHas('web_payments', [
            'codigo_t' => null,
            'gateway_status' => 'approved',
            'card_brand' => 'visa',
            'card_last_four' => '1234',
        ]);
    }

    public function test_hold_temporal_bloquea_butaca_durante_pago(): void
    {
        $d = $this->setupSesion();
        $user = $this->setupUser('11111111111');
        $otroUser = $this->setupUser('22222222222');

        DB::table('seat_holds')->insert([
            'hold_token' => '00000000-0000-0000-0000-000000000001',
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $otroUser->ci,
            'id_b' => $d['b1']->id_b,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $user->ci,
            'cantidad' => 10,
            'codigo_t' => '4532123412341234',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ])->assertStatus(409);

        $this->assertDatabaseMissing('compras', [
            'ci' => $user->ci,
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
        ]);
    }

    public function test_hold_expirado_no_bloquea_compra(): void
    {
        $d = $this->setupSesion();
        $user = $this->setupUser('11111111111');
        $otroUser = $this->setupUser('22222222222');

        DB::table('seat_holds')->insert([
            'hold_token' => '00000000-0000-0000-0000-000000000002',
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $otroUser->ci,
            'id_b' => $d['b1']->id_b,
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/compras/tarjeta', [
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $user->ci,
            'cantidad' => 10,
            'codigo_t' => '4532123412341234',
            'fecha_de_compra' => '2025-12-20 10:00:00',
            'butaca_ids' => [$d['b1']->id_b],
        ])->assertStatus(201);

        $this->assertDatabaseMissing('seat_holds', [
            'hold_token' => '00000000-0000-0000-0000-000000000002',
        ]);
    }

    public function test_comando_limpia_holds_expirados(): void
    {
        $d = $this->setupSesion();
        $user = $this->setupUser('11111111111');

        DB::table('seat_holds')->insert([
            'hold_token' => '00000000-0000-0000-0000-000000000003',
            'id_p' => $d['p']->id_p,
            'id_s' => $d['s']->id_s,
            'fecha' => '2025-12-25 20:00:00',
            'ci' => $user->ci,
            'id_b' => $d['b1']->id_b,
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);

        $this->artisan('seat-holds:clear-expired')
            ->expectsOutput('Expired seat holds deleted: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('seat_holds', [
            'hold_token' => '00000000-0000-0000-0000-000000000003',
        ]);
    }
}
