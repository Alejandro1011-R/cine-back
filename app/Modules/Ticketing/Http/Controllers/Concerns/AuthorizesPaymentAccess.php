<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Controllers\Concerns;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait AuthorizesPaymentAccess
{
    private function paginated(string $resourceClass, Builder $query, Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        return response()->json($resourceClass::collection($query->paginate($perPage))->response()->getData(true));
    }

    private function ensureBackoffice(Request $request): void
    {
        /** @var Usuario $user */
        $user = $request->user();

        if (!$this->isBackoffice($user)) {
            abort(403);
        }
    }

    private function isBackoffice(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value], true);
    }

    private function scopePagoQuery(Builder $query, Request $request): void
    {
        /** @var Usuario $user */
        $user = $request->user();

        if ($user->rol === UserRole::ADMIN->value) {
            $query->whereHas('compra', function (Builder $compraQuery) use ($user): void {
                $this->scopeCompraToAssignedCines($compraQuery, $user);
            });
        }
    }

    private function scopePaymentDetailQuery(Builder $query, Request $request): void
    {
        /** @var Usuario $user */
        $user = $request->user();

        if ($user->rol === UserRole::ADMIN->value) {
            $query->whereHas('pago.compra', function (Builder $compraQuery) use ($user): void {
                $this->scopeCompraToAssignedCines($compraQuery, $user);
            });
        }
    }

    private function scopeCompraToAssignedCines(Builder $query, Usuario $user): void
    {
        $query->whereExists(function ($subQuery) use ($user): void {
            $subQuery->selectRaw('1')
                ->from('sesiones')
                ->join('salas', 'sesiones.id_s', '=', 'salas.id_s')
                ->join('cine_usuario', 'salas.id_c', '=', 'cine_usuario.id_c')
                ->whereColumn('sesiones.id_p', 'compras.id_p')
                ->whereColumn('sesiones.id_s', 'compras.id_s')
                ->whereColumn('sesiones.fecha', 'compras.fecha')
                ->where('cine_usuario.ci', $user->ci);
        });
    }

    private function technicalWriteDisabled(): JsonResponse
    {
        return response()->json([
            'message' => 'Este recurso se gestiona desde los flujos de compra.',
        ], 405);
    }
}
