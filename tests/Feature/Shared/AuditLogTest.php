<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Shared\Infrastructure\Audit\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditLogTest extends TestCase
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

    public function test_super_admin_puede_listar_auditoria(): void
    {
        $superAdmin = $this->createUsuario('99999999999', 'SuperAdmin');

        AuditLog::create([
            'actor_ci' => $superAdmin->ci,
            'action' => 'cinema.staff.assigned',
            'auditable_type' => 'cine',
            'auditable_id' => '1',
            'metadata' => ['staff_ci' => '11111111111'],
        ]);

        $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/audit-logs?action=cinema.staff.assigned')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'cinema.staff.assigned');
    }

    public function test_no_super_admin_no_puede_listar_auditoria(): void
    {
        $admin = $this->createUsuario('99999999999', 'Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit-logs')
            ->assertForbidden();
    }
}
