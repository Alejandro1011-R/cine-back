<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Controllers;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Ticketing\Application\Actions\CancelCompraAction;
use App\Modules\Ticketing\Application\Actions\CompraByTaquillaEfectivoAction;
use App\Modules\Ticketing\Application\Actions\CompraByUserPuntosAction;
use App\Modules\Ticketing\Application\Actions\CompraByUserTarjetaAction;
use App\Modules\Ticketing\Application\DTOs\CompraByPuntosData;
use App\Modules\Ticketing\Application\DTOs\CompraByTaquillaData;
use App\Modules\Ticketing\Application\DTOs\CompraByTarjetaData;
use App\Modules\Ticketing\Http\Requests\CompraByPuntosRequest;
use App\Modules\Ticketing\Http\Requests\CompraByTaquillaRequest;
use App\Modules\Ticketing\Http\Requests\CompraByTarjetaRequest;
use App\Modules\Ticketing\Http\Resources\CompraResource;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CompraController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CompraByUserTarjetaAction $tarjetaAction,
        private readonly CompraByTaquillaEfectivoAction $taquillaAction,
        private readonly CompraByUserPuntosAction $puntosAction,
        private readonly CancelCompraAction $cancelAction,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = auth()->user();

        $query = Compra::with(['pago.efectivo', 'pago.punto', 'pago.webPayment', 'cliente']);

        if ($user->rol === UserRole::SUPER_ADMIN->value) {
        } elseif (in_array($user->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)) {
            $query->join('sesiones', function ($join) {
                $join->on('compras.id_p', '=', 'sesiones.id_p')
                    ->on('compras.id_s', '=', 'sesiones.id_s')
                    ->on('compras.fecha', '=', 'sesiones.fecha');
            })
            ->join('salas', 'sesiones.id_s', '=', 'salas.id_s')
            ->join('cine_usuario', 'salas.id_c', '=', 'cine_usuario.id_c')
            ->where('cine_usuario.ci', $user->ci)
            ->select('compras.*');
        } else {
            $query->where('compras.ci', $user->ci);
        }

        $perPage = min($request->integer('per_page', 15), 100);
        return response()->json(CompraResource::collection($query->paginate($perPage))->response()->getData(true));
    }

    public function show(int $id): JsonResponse
    {
        $compra = Compra::with(['pago.efectivo', 'pago.punto', 'pago.webPayment', 'cliente'])
            ->where('id_pg', $id)
            ->first();

        if ($compra === null) {
            return response()->json(['message' => 'Compra no encontrada.'], 404);
        }

        $this->authorize('view', $compra);

        return response()->json(new CompraResource($compra));
    }

    public function compraByTarjeta(CompraByTarjetaRequest $request): JsonResponse
    {
        $v = $request->validated();
        /** @var Usuario $user */
        $user = $request->user();

        if ($user->ci !== $v['ci']) {
            return response()->json(['message' => 'No puede comprar con el CI de otro usuario.'], 403);
        }

        $data = new CompraByTarjetaData(
            idP: (int) $v['id_p'],
            idS: (int) $v['id_s'],
            fecha: $v['fecha'],
            ci: $v['ci'],
            cantidad: isset($v['cantidad']) ? (float) $v['cantidad'] : null,
            codigoT: $v['codigo_t'],
            fechaDeCompra: $v['fecha_de_compra'],
            butacaIds: $v['butaca_ids'],
            descuentoIds: $v['descuento_ids'] ?? [],
        );

        $result = $this->tarjetaAction->handle($data);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(new CompraResource($result['compra']), 201);
    }

    public function compraByTaquilla(CompraByTaquillaRequest $request): JsonResponse
    {
        $v = $request->validated();
        /** @var Usuario $user */
        $user = $request->user();

        if ($user->ci !== $v['ci_taquillero']) {
            return response()->json(['message' => 'El taquillero autenticado no coincide con la venta.'], 403);
        }

        $data = new CompraByTaquillaData(
            idP: (int) $v['id_p'],
            idS: (int) $v['id_s'],
            fecha: $v['fecha'],
            ciTaquillero: $v['ci_taquillero'],
            ci: $v['ci'],
            cantidad: isset($v['cantidad']) ? (float) $v['cantidad'] : null,
            correo: $v['correo'],
            fechaDeCompra: $v['fecha_de_compra'],
            butacaIds: $v['butaca_ids'],
            descuentoIds: $v['descuento_ids'] ?? [],
        );

        $result = $this->taquillaAction->handle($data);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(new CompraResource($result['compra']), 201);
    }

    public function compraByPuntos(CompraByPuntosRequest $request): JsonResponse
    {
        $v = $request->validated();
        /** @var Usuario $user */
        $user = $request->user();

        if ($user->ci !== $v['ci']) {
            return response()->json(['message' => 'No puede comprar con los puntos de otro usuario.'], 403);
        }

        $data = new CompraByPuntosData(
            idP: (int) $v['id_p'],
            idS: (int) $v['id_s'],
            fecha: $v['fecha'],
            ci: $v['ci'],
            cantidad: isset($v['cantidad']) ? (int) $v['cantidad'] : null,
            fechaDeCompra: $v['fecha_de_compra'],
            butacaIds: $v['butaca_ids'],
        );

        $result = $this->puntosAction->handle($data);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(new CompraResource($result['compra']), 201);
    }

    public function destroy(int $idP, int $idS, string $fecha, string $ci, int $idPg): JsonResponse
    {
        $compra = Compra::where('id_p', $idP)
            ->where('id_s', $idS)
            ->where('fecha', $fecha)
            ->where('ci', $ci)
            ->where('id_pg', $idPg)
            ->first();

        if ($compra === null) {
            return response()->json(['message' => 'No se encuentra este ticket.'], 404);
        }

        $this->authorize('delete', $compra);

        /** @var Usuario $user */
        $user = request()->user();
        $result = $this->cancelAction->handle($idP, $idS, $fecha, $ci, $idPg, $user->ci);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(null, 204);
    }
}
