<?php

declare(strict_types=1);

namespace Tests\Feature\Ticketing;

use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createUsuario(string $ci, string $rol): Usuario
    {
        Cliente::create(['ci' => $ci, 'correo' => "{$ci}@test.com"]);

        return Usuario::create([
            'ci' => $ci,
            'nombre_s' => 'User',
            'contrasena' => 'x',
            'codigo' => $ci,
            'rol' => $rol,
            'puntos' => 0,
        ]);
    }

    public function test_cliente_no_puede_listar_pagos_tecnicos(): void
    {
        $cliente = $this->createUsuario('11111111111', 'Cliente');

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/pagos')
            ->assertForbidden();
    }

    public function test_no_permite_crear_web_payment_directamente(): void
    {
        $admin = $this->createUsuario('99999999999', 'Admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/web-payments', [
                'id_pg' => 1,
                'gateway_reference' => 'SIM-EXTERNAL',
                'gateway_status' => 'approved',
                'cantidad' => 20,
            ])
            ->assertStatus(405)
            ->assertJsonFragment(['message' => 'Este recurso se gestiona desde los flujos de compra.']);
    }

    public function test_no_existen_endpoints_para_guardar_tarjetas(): void
    {
        $cliente = $this->createUsuario('11111111111', 'Cliente');

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/tarjetas', [
                'codigo_t' => '123456789012345678',
                'ci' => $cliente->ci,
            ])
            ->assertNotFound();

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/tarjetas')
            ->assertNotFound();
    }

    public function test_crud_descuentos_queda_auditado(): void
    {
        $admin = $this->createUsuario('99999999999', 'Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/descuentos', [
                'nombre_d' => 'Promo',
                'porciento' => 10,
            ])
            ->assertCreated();

        $id = $response->json('id_d');
        $this->assertDatabaseHas('audit_logs', [
            'actor_ci' => $admin->ci,
            'action' => 'ticketing.discount.created',
            'auditable_id' => (string) $id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/descuentos/{$id}", [
                'nombre_d' => 'Promo 2',
                'porciento' => 15,
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'actor_ci' => $admin->ci,
            'action' => 'ticketing.discount.updated',
            'auditable_id' => (string) $id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/descuentos/{$id}")
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'actor_ci' => $admin->ci,
            'action' => 'ticketing.discount.deleted',
            'auditable_id' => (string) $id,
        ]);
    }
}
