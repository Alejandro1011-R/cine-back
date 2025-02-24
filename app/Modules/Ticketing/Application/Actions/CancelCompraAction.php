<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Actions;

use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Domain\Enums\TipoCompra;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Ticketing\Domain\Events\CompraCancelled;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Shared\Infrastructure\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class CancelCompraAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{success: bool, error?: string, code?: int}
     */
    public function handle(int $idP, int $idS, string $fecha, string $ci, int $idPg, ?string $actorCi = null): array
    {
        $fechaCarbon = Carbon::parse($fecha);

        // Business rule: can only cancel at least 2 hours before the session
        if ($fechaCarbon->diffInHours(Carbon::now(), false) > -2) {
            return [
                'success' => false,
                'error' => 'Solo puede cancelar la entrada al menos 2 horas antes de que empiece la pelicula.',
                'code' => 400,
            ];
        }

        return DB::transaction(function () use ($idP, $idS, $fecha, $ci, $idPg, $actorCi): array {
            $compra = Compra::with(['pago.punto', 'pago.efectivo', 'pago.webPayment'])
                ->where('id_p', $idP)
                ->where('id_s', $idS)
                ->where('fecha', $fecha)
                ->where('ci', $ci)
                ->where('id_pg', $idPg)
                ->lockForUpdate()
                ->first();

            if ($compra === null) {
                return ['success' => false, 'error' => 'No se encuentra este ticket.', 'code' => 404];
            }

            $usuario = Usuario::where('ci', $compra->ci)->lockForUpdate()->first();

            if ($compra->tipo === TipoCompra::PUNTOS->value) {
                $pago = $compra->pago;
                if ($usuario !== null && $pago !== null && $pago->punto !== null) {
                    $usuario->puntos = ($usuario->puntos ?? 0) + ($pago->punto->gastados ?? 0);
                    $usuario->save();
                } else {
                    return ['success' => false, 'error' => 'El pago no fue hecho.', 'code' => 400];
                }
            } elseif ($usuario !== null && $usuario->rol !== UserRole::CLIENTE->value) {
                $butacaCount = $compra->butacas->count();
                $pointsToDeduct = $butacaCount * 5;
                if (($usuario->puntos ?? 0) - $pointsToDeduct < 0) {
                    return [
                        'success' => false,
                        'error' => 'El usuario ha gastado todos los puntos asignados por esta compra.',
                        'code' => 400,
                    ];
                }
                $usuario->puntos = ($usuario->puntos ?? 0) - $pointsToDeduct;
                $usuario->save();
            }

            // Delete pivot entries and compra
            DB::table('butacas_reservadas')
                ->where('id_p', $compra->id_p)
                ->where('id_s', $compra->id_s)
                ->where('fecha', $compra->fecha)
                ->where('ci', $compra->ci)
                ->delete();

            DB::table('descontados')
                ->where('id_p', $compra->id_p)
                ->where('id_s', $compra->id_s)
                ->where('fecha', $compra->fecha)
                ->where('ci', $compra->ci)
                ->delete();

            $paymentId = $compra->id_pg;
            $customerCi = $compra->ci;

            $compra->delete();

            CompraCancelled::dispatch($paymentId, $customerCi);
            $this->auditLogger->record(
                actorCi: $actorCi ?? $customerCi,
                action: 'ticketing.purchase.cancelled',
                auditableType: Compra::class,
                auditableId: (string) $paymentId,
                metadata: [
                    'id_p' => $compra->id_p,
                    'id_s' => $compra->id_s,
                    'fecha' => (string) $compra->fecha,
                    'ci' => $customerCi,
                ],
            );

            return ['success' => true];
        });
    }
}
