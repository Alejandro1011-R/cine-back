<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Infrastructure\Persistence\Models\RoleChangeAudit;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Shared\Infrastructure\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final class ChangeUserRoleAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(string $ci, string $rol, ?string $actorCi = null): ?Usuario
    {
        return DB::transaction(function () use ($ci, $rol, $actorCi): ?Usuario {
            $usuario = Usuario::where('ci', $ci)->lockForUpdate()->first();

            if ($usuario === null) {
                return null;
            }

            $oldRole = $usuario->rol;
            $usuario->rol = $rol;
            $usuario->save();

            if ($actorCi !== null && $oldRole !== $rol) {
                RoleChangeAudit::create([
                    'actor_ci' => $actorCi,
                    'target_ci' => $usuario->ci,
                    'old_role' => $oldRole,
                    'new_role' => $rol,
                ]);

                $this->auditLogger->record(
                    actorCi: $actorCi,
                    action: 'identity.user.role_changed',
                    auditableType: Usuario::class,
                    auditableId: $usuario->ci,
                    metadata: [
                        'old_role' => $oldRole,
                        'new_role' => $rol,
                    ],
                );
            }

            $usuario->load('cliente');

            return $usuario;
        });
    }
}
