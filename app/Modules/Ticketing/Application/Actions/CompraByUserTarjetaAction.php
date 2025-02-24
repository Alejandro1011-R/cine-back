<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Actions;

use App\Modules\Ticketing\Application\DTOs\CardPaymentData;
use App\Modules\Ticketing\Application\DTOs\CompraByTarjetaData;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Domain\Contracts\PaymentGateway;
use App\Modules\Ticketing\Domain\Contracts\SeatAvailabilityChecker;
use App\Modules\Ticketing\Domain\Contracts\SeatHoldManager;
use App\Modules\Ticketing\Domain\Enums\MedioAdquisicion;
use App\Modules\Ticketing\Domain\Enums\TipoCompra;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Ticketing\Domain\Events\CompraCreated;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Descuento;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Pago;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\WebPayment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CompraByUserTarjetaAction
{
    public function __construct(
        private readonly SeatAvailabilityChecker $seatAvailabilityChecker,
        private readonly PaymentGateway $paymentGateway,
        private readonly SeatHoldManager $seatHoldManager,
    ) {}

    /**
     * @return array{success: bool, compra?: Compra, error?: string, code?: int}
     */
    public function handle(CompraByTarjetaData $data): array
    {
        $usuario = Usuario::with('cliente')->find($data->ci);
        if ($usuario === null) {
            return ['success' => false, 'error' => 'El usuario no existe.', 'code' => 404];
        }

        if ($usuario->cliente->confiabilidad === false && count($data->descuentoIds) > 0) {
            return ['success' => false, 'error' => 'El usuario no puede asignarse descuentos.', 'code' => 400];
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

        if (!empty($data->descuentoIds)) {
            $descuentos = Descuento::whereIn('id_d', $data->descuentoIds)->get();
            if ($descuentos->count() !== count($data->descuentoIds)) {
                return ['success' => false, 'error' => 'Uno o más descuentos no existen.', 'code' => 400];
            }
        }

        if ($this->seatAvailabilityChecker->hasReservedSeats($data->idP, $data->idS, $data->fecha, $data->butacaIds)) {
            return ['success' => false, 'error' => 'Una o más butacas ya están reservadas.', 'code' => 409];
        }

        $holdToken = $this->seatHoldManager->acquire($data->idP, $data->idS, $data->fecha, $data->ci, $data->butacaIds);
        if ($holdToken === null) {
            return ['success' => false, 'error' => 'Una o más butacas están temporalmente bloqueadas por otra compra.', 'code' => 409];
        }

        $paymentResult = $this->paymentGateway->charge(new CardPaymentData(
            cardNumber: $data->codigoT,
            amount: $data->cantidad,
            customerCi: $data->ci,
        ));

        if (!$paymentResult->approved) {
            $this->seatHoldManager->release($holdToken);

            return [
                'success' => false,
                'error' => $paymentResult->declineReason ?? 'Pago rechazado por la pasarela.',
                'code' => 402,
            ];
        }

        try {
            return DB::transaction(function () use ($data, $usuario, $paymentResult, $holdToken): array {
                $pago = new Pago();
                $pago->save();

                WebPayment::create([
                    'id_pg' => $pago->id_pg,
                    'codigo_t' => null,
                    'cantidad' => $data->cantidad,
                    'gateway_reference' => $paymentResult->reference,
                    'gateway_status' => $paymentResult->status,
                    'card_brand' => $paymentResult->brand,
                    'card_last_four' => $paymentResult->lastFour,
                ]);

                $compra = new Compra();
                $compra->id_p = $data->idP;
                $compra->id_s = $data->idS;
                $compra->fecha = $data->fecha;
                $compra->ci = $data->ci;
                $compra->id_pg = $pago->id_pg;
                $compra->tipo = TipoCompra::TARJETA->value;
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

                foreach ($data->descuentoIds as $idD) {
                    DB::table('descontados')->insert([
                        'id_p' => $data->idP,
                        'id_s' => $data->idS,
                        'fecha' => $data->fecha,
                        'ci' => $data->ci,
                        'id_d' => $idD,
                    ]);
                }

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

                $compra->load(['pago.webPayment', 'cliente']);

                CompraCreated::dispatch($compra->id_pg, $compra->ci);

                $this->seatHoldManager->release($holdToken);

                return ['success' => true, 'compra' => $compra];
            });
        } catch (UniqueConstraintViolationException) {
            $this->seatHoldManager->release($holdToken);

            return ['success' => false, 'error' => 'Una de las butacas fue reservada por otro usuario mientras realizabas la compra.', 'code' => 409];
        } catch (Throwable $exception) {
            $this->seatHoldManager->release($holdToken);

            throw $exception;
        }
    }
}
