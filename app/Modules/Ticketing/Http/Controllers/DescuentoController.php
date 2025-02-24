<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Controllers;

use App\Modules\Ticketing\Http\Controllers\Concerns\AuthorizesPaymentAccess;
use App\Modules\Ticketing\Http\Resources\DescuentoResource;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Descuento;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Shared\Infrastructure\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class DescuentoController extends Controller
{
    use AuthorizesPaymentAccess;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureBackoffice($request);

        return $this->paginated(DescuentoResource::class, Descuento::query(), $request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $descuento = Descuento::find($id);

        return $descuento ? response()->json(new DescuentoResource($descuento)) : response()->json(['message' => 'Descuento no encontrado.'], 404);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureBackoffice($request);

        $validated = $request->validate([
            'nombre_d' => ['nullable', 'string', 'max:30'],
            'porciento' => ['nullable', 'numeric'],
        ]);

        $descuento = Descuento::create($validated);
        /** @var Usuario $actor */
        $actor = $request->user();
        $this->auditLogger->record($actor->ci, 'ticketing.discount.created', Descuento::class, (string) $descuento->id_d, $validated);

        return response()->json(new DescuentoResource($descuento), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $descuento = Descuento::find($id);
        if ($descuento === null) {
            return response()->json(['message' => 'Descuento no encontrado.'], 404);
        }

        $validated = $request->validate([
            'nombre_d' => ['nullable', 'string', 'max:30'],
            'porciento' => ['nullable', 'numeric'],
        ]);

        $descuento->update($validated);
        /** @var Usuario $actor */
        $actor = $request->user();
        $this->auditLogger->record($actor->ci, 'ticketing.discount.updated', Descuento::class, (string) $descuento->id_d, $validated);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ensureBackoffice($request);

        $descuento = Descuento::find($id);
        if ($descuento === null) {
            return response()->json(['message' => 'Descuento no encontrado.'], 404);
        }

        /** @var Usuario $actor */
        $actor = $request->user();
        $this->auditLogger->record($actor->ci, 'ticketing.discount.deleted', Descuento::class, (string) $descuento->id_d, [
            'nombre_d' => $descuento->nombre_d,
            'porciento' => $descuento->porciento,
        ]);

        $descuento->delete();

        return response()->json(null, 204);
    }
}
