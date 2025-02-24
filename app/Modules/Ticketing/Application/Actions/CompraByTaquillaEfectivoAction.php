<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Actions;

use App\Modules\Ticketing\Application\DTOs\CompraByTaquillaData;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Domain\Enums\TipoCompra;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Ticketing\Domain\Contracts\SeatAvailabilityChecker;
use App\Modules\Ticketing\Domain\Events\CompraCreated;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Descuento;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Efectivo;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Pago;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CompraByTaquillaEfectivoAction
{
    public function __construct(
        private readonly SeatAvailabilityChecker $seatAvailabilityChecker,
    ) {}

    /**
     * @return array{success: bool, compra?: Compra, error?: string, code?: int}
     */
    public function handle(CompraByTaquillaData $data): array
    {
        $taquillero = Usuario::find($data->ciTaquillero);
        if ($taquillero === null || $taquillero->rol === UserRole::CLIENTE->value || $taquillero->rol === UserRole::SOCIO->value) {
            return ['success' => false, 'error' => 'El usuario que efectua la venta no existe o no es taquillero.', 'code' => 400];
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

        if (!empty($data->descuentoIds)) {
            $descuentos = Descuento::whereIn('id_d', $data->descuentoIds)->get();
            if ($descuentos->count() !== count($data->descuentoIds)) {
                return ['success' => false, 'error' => 'Uno o más descuentos no existen.', 'code' => 400];
            }
        }

        $sesion = Sesion::where('id_p', $data->idP)
            ->where('id_s', $data->idS)
            ->where('fecha', $data->fecha)
            ->first();
        if ($sesion === null) {
            return ['success' => false, 'error' => 'La sesion no existe.', 'code' => 404];
        }

        $sesion->load('sala');
        if ($sesion->sala?->id_c === null || !$taquillero->belongsToCine((int) $sesion->sala->id_c)) {
            return ['success' => false, 'error' => 'El taquillero no pertenece al cine de la sesión.', 'code' => 403];
        }

        try {
            return DB::transaction(function () use ($data): array {
                if ($this->seatAvailabilityChecker->hasReservedSeats($data->idP, $data->idS, $data->fecha, $data->butacaIds)) {
                    return ['success' => false, 'error' => 'Una o más butacas ya están reservadas.', 'code' => 409];
                }

                $cliente = Cliente::find($data->ci);
                if ($cliente === null) {
                    $cliente = Cliente::create([
                        'ci' => $data->ci,
                        'correo' => $data->correo,
                        'confiabilidad' => true,
                    ]);
                }

                if ($cliente->confiabilidad === false && count($data->descuentoIds) > 0) {
                    return ['success' => false, 'error' => 'El cliente no puede asignarse descuentos.', 'code' => 400];
                }

                $pago = new Pago();
                $pago->save();

                Efectivo::create([
                    'id_pg' => $pago->id_pg,
                    'cantidad_e' => $data->cantidad,
                ]);

                $compra = new Compra();
                $compra->id_p = $data->idP;
                $compra->id_s = $data->idS;
                $compra->fecha = $data->fecha;
                $compra->ci = $data->ci;
                $compra->id_pg = $pago->id_pg;
                $compra->tipo = TipoCompra::EFECTIVO->value;
                $compra->fecha_de_compra = $data->fechaDeCompra;
                $compra->medio_ad = $data->ciTaquillero;
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

                foreach ($data->descuentoIds as $idD) {
                    DB::table('descontados')->insert([
                        'id_p' => $data->idP,
                        'id_s' => $data->idS,
                        'fecha' => $data->fecha,
                        'ci' => $data->ci,
                        'id_d' => $idD,
                    ]);
                }

                $usuario = Usuario::find($data->ci);
                if ($usuario !== null) {
                    if ($usuario->rol !== UserRole::CLIENTE->value) {
                        $usuario->puntos = ($usuario->puntos ?? 0) + 5 * count($data->butacaIds);
                        $usuario->save();
                    } else {
                        $compraCount = Compra::where('ci', $data->ci)->count();
                        if ($compraCount >= 10) {
                            $usuario->rol = UserRole::SOCIO->value;
                            $usuario->puntos = ($usuario->puntos ?? 0) + 5 * count($data->butacaIds);
                            $usuario->save();
                        }
                    }
                }

                $compra->load(['pago.efectivo', 'cliente']);

                CompraCreated::dispatch($compra->id_pg, $compra->ci);

                return ['success' => true, 'compra' => $compra];
            });
        } catch (UniqueConstraintViolationException) {
            return ['success' => false, 'error' => 'Una de las butacas fue reservada por otro usuario mientras realizabas la compra.', 'code' => 409];
        }
    }
}
