<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Controllers;

use App\Modules\Ticketing\Http\Controllers\Concerns\AuthorizesPaymentAccess;
use App\Modules\Ticketing\Http\Resources\EfectivoResource;
use App\Modules\Ticketing\Http\Resources\PagoResource;
use App\Modules\Ticketing\Http\Resources\PuntoPagoResource;
use App\Modules\Ticketing\Http\Resources\WebPaymentResource;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Efectivo;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Pago;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Punto;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\WebPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PagoController extends Controller
{
    use AuthorizesPaymentAccess;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = Pago::with(['efectivo', 'punto', 'webPayment', 'compra.cliente']);
        $this->scopePagoQuery($query, $request);

        return $this->paginated(PagoResource::class, $query, $request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = Pago::with(['efectivo', 'punto', 'webPayment', 'compra.cliente'])->where('id_pg', $id);
        $this->scopePagoQuery($query, $request);

        $pago = $query->first();

        return $pago ? response()->json(new PagoResource($pago)) : response()->json(['message' => 'Pago no encontrado.'], 404);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->technicalWriteDisabled();
    }

    public function technicalWrite(): JsonResponse
    {
        return $this->technicalWriteDisabled();
    }

    public function efectivosIndex(Request $request): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = Efectivo::query();
        $this->scopePaymentDetailQuery($query, $request);

        return $this->paginated(EfectivoResource::class, $query, $request);
    }

    public function efectivosShow(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = Efectivo::where('id_pg', $id);
        $this->scopePaymentDetailQuery($query, $request);

        $efectivo = $query->first();

        return $efectivo ? response()->json(new EfectivoResource($efectivo)) : response()->json(['message' => 'No encontrado.'], 404);
    }

    public function puntosIndex(Request $request): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = Punto::query();
        $this->scopePaymentDetailQuery($query, $request);

        return $this->paginated(PuntoPagoResource::class, $query, $request);
    }

    public function puntosShow(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = Punto::where('id_pg', $id);
        $this->scopePaymentDetailQuery($query, $request);

        $punto = $query->first();

        return $punto ? response()->json(new PuntoPagoResource($punto)) : response()->json(['message' => 'No encontrado.'], 404);
    }

    public function webPaymentsIndex(Request $request): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = WebPayment::query();
        $this->scopePaymentDetailQuery($query, $request);

        return $this->paginated(WebPaymentResource::class, $query, $request);
    }

    public function webPaymentsShow(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $query = WebPayment::where('id_pg', $id);
        $this->scopePaymentDetailQuery($query, $request);

        $webPayment = $query->first();

        return $webPayment ? response()->json(new WebPaymentResource($webPayment)) : response()->json(['message' => 'No encontrado.'], 404);
    }
}
