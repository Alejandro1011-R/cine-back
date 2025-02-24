<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Actions;

use App\Modules\Ticketing\Application\DTOs\CompraByPuntosData;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Domain\Enums\MedioAdquisicion;
use App\Modules\Ticketing\Domain\Enums\TipoCompra;
use App\Modules\Ticketing\Domain\Contracts\SeatAvailabilityChecker;
use App\Modules\Ticketing\Domain\Events\CompraCreated;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Pago;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Punto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CompraByUserPuntosAction
{
    public function __construct(
        private readonly SeatAvailabilityChecker $seatAvailabilityChecker,
    ) {}

    /**
     * @return array{success: bool, compra?: Compra, error?: string, code?: int}
     */
    public function handle(CompraByPuntosData $data): array
    {
        $usuario = Usuario::with('cliente')->find($data->ci);
        if ($usuario === null) {
            return ['success' => false, 'error' => 'El usuario no existe.', 'code' => 404];
        }

        if (empty($data->butacaIds)) {
            return ['success' => false, 'error' => 'No estas seleccionando ninguna butaca.', 'code' => 400];
        }

        $butacas = Butaca::whereIn('id_b', $data->butacaIds)->get();
        if ($butacas->count() !== count($data->butacaIds)) {
            return ['success' => false, 'error' => 'Una o más butacas no existen.', 'code' => 400];
        }

        if ($butacas->contains(fn (Butaca $butaca): bool => (int) $butaca->id_s !== $data->idS)) {
            return ['success' => false, 'error' => 'Una o más butacas no pertenecen a la sala de la sesión.', 'code' => 400];
        }

        $sesion = Sesion::where('id_p', $data->idP)
            ->where('id_s', $data->idS)
            ->where('fecha', $data->fecha)
            ->first();
        if ($sesion === null) {
            return ['success' => false, 'error' => 'La sesion no existe.', 'code' => 404];
        }

        try {
            return DB::transaction(function () use ($data): array {
                $usuario = Usuario::where('ci', $data->ci)->lockForUpdate()->first();
                if ($usuario === null) {
                    return ['success' => false, 'error' => 'El usuario no existe.', 'code' => 404];
                }

                if (($usuario->puntos ?? 0) < ($data->cantidad ?? 0)) {
                    return ['success' => false, 'error' => 'El usuario no tiene suficientes puntos.', 'code' => 400];
                }

                if ($this->seatAvailabilityChecker->hasReservedSeats($data->idP, $data->idS, $data->fecha, $data->butacaIds)) {
                    return ['success' => false, 'error' => 'Una o más butacas ya están reservadas.', 'code' => 409];
                }

                $pago = new Pago();
                $pago->save();

                Punto::create([
                    'id_pg' => $pago->id_pg,
                    'gastados' => $data->cantidad,
                ]);

                $compra = new Compra();
                $compra->id_p = $data->idP;
                $compra->id_s = $data->idS;
                $compra->fecha = $data->fecha;
                $compra->ci = $data->ci;
                $compra->id_pg = $pago->id_pg;
                $compra->tipo = TipoCompra::PUNTOS->value;
                $compra->fecha_de_compra = $data->fechaDeCompra;
                $compra->medio_ad = MedioAdquisicion::WEB->value;
                $compra->save();

                foreach ($data->butacaIds as $idB) {
                    DB::table('butacas_reservadas')->insert([
                        'id_p' => $data->idP,
                        'id_s' => $data->idS,
                        'fecha' => $data->fecha,
                        'ci' => $data->ci,
                        'id_b' => $idB,
                    ]);
                }

                $usuario->puntos = ($usuario->puntos ?? 0) - ($data->cantidad ?? 0);
                $usuario->save();

                $compra->load(['pago.punto', 'cliente']);

                CompraCreated::dispatch($compra->id_pg, $compra->ci);

                return ['success' => true, 'compra' => $compra];
            });
        } catch (UniqueConstraintViolationException) {
            return ['success' => false, 'error' => 'Una de las butacas fue reservada por otro usuario mientras realizabas la compra.', 'code' => 409];
        }
    }
}
