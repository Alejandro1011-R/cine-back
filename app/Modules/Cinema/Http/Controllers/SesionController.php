<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Controllers;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Cinema\Application\Actions\CreateSesionAction;
use App\Modules\Cinema\Application\DTOs\CreateSesionData;
use App\Modules\Cinema\Http\Requests\CreateSesionRequest;
use App\Modules\Cinema\Http\Resources\SesionResource;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Shared\Infrastructure\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class SesionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreateSesionAction $createAction,
        private readonly AuditLogger $auditLogger,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Sesion::class);

        $perPage = min($request->integer('per_page', 15), 100);
        $query = Sesion::with(['pelicula', 'sala']);
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();
        if (in_array($user->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)) {
            $query->whereHas('sala.cine.staff', fn ($staff) => $staff->where('usuarios.ci', $user->ci));
        }

        return SesionResource::collection($query->paginate($perPage));
    }

    public function byPelicula(Request $request, int $peliculaId): JsonResponse
    {
        $pelicula = Pelicula::with('sesiones.sala')->find($peliculaId);
        if ($pelicula === null) {
            return response()->json(['message' => 'Película no encontrada.'], 404);
        }
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();
        $sesiones = $pelicula->sesiones;
        if (in_array($user->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)) {
            $sesiones = $sesiones->filter(fn (Sesion $sesion): bool => $sesion->sala?->id_c !== null && $user->belongsToCine((int) $sesion->sala->id_c))->values();
        }

        return response()->json(SesionResource::collection($sesiones));
    }

    public function show(int $idP, int $idS, string $fecha): JsonResponse
    {
        $sesion = $this->findSesion($idP, $idS, $fecha);
        if ($sesion === null) {
            return response()->json(['message' => 'Sesión no encontrada.'], 404);
        }

        $this->authorize('view', $sesion);

        return response()->json(new SesionResource($sesion));
    }

    public function store(CreateSesionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->authorize('create', Sesion::class);
        $sala = \App\Modules\Cinema\Infrastructure\Persistence\Models\Sala::find((int) $validated['id_s']);
        if ($sala === null) {
            return response()->json(['message' => 'Sala no encontrada.'], 400);
        }
        $this->authorize('update', $sala);

        $data = new CreateSesionData(
            idP: (int) $validated['id_p'],
            idS: (int) $validated['id_s'],
            fecha: Carbon::parse($validated['fecha']),
        );

        $result = $this->createAction->handle($data);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        /** @var Usuario $actor */
        $actor = $request->user();
        $this->auditLogger->record(
            actorCi: $actor->ci,
            action: 'cinema.session.created',
            auditableType: Sesion::class,
            auditableId: $validated['id_p'] . ':' . $validated['id_s'] . ':' . $validated['fecha'],
            metadata: [
                'id_p' => (int) $validated['id_p'],
                'id_s' => (int) $validated['id_s'],
                'fecha' => $validated['fecha'],
            ],
        );

        return response()->json(new SesionResource($result['sesion']), 201);
    }

    public function destroy(int $idP, int $idS, string $fecha): JsonResponse
    {
        $sesion = $this->findSesion($idP, $idS, $fecha);
        if ($sesion === null) {
            return response()->json(['message' => 'Sesión no encontrada.'], 404);
        }
        $this->authorize('delete', $sesion);
        $sesion->delete();
        return response()->json(null, 204);
    }

    private function findSesion(int $idP, int $idS, string $fecha): ?Sesion
    {
        return Sesion::with(['pelicula', 'sala'])
            ->where('id_p', $idP)
            ->where('id_s', $idS)
            ->where('fecha', Carbon::parse($fecha))
            ->first();
    }
}
