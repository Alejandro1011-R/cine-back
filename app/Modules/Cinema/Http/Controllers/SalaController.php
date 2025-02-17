<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Controllers;

use App\Modules\Cinema\Http\Requests\StoreSalaRequest;
use App\Modules\Cinema\Http\Resources\SalaResource;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Identity\Domain\Enums\UserRole;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class SalaController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Sala::class);

        $perPage = min($request->integer('per_page', 15), 100);
        $query = Sala::query();
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();

        if (in_array($user->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)) {
            $query->whereHas('cine.staff', fn ($staff) => $staff->where('usuarios.ci', $user->ci));
        }

        return SalaResource::collection($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $sala = Sala::find($id);
        if ($sala) {
            $this->authorize('view', $sala);
        }
        return $sala ? response()->json(new SalaResource($sala)) : response()->json(['message' => 'Sala no encontrada.'], 404);
    }

    public function store(StoreSalaRequest $request): JsonResponse
    {
        $this->authorize('create', Sala::class);
        $validated = $request->validated();
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();
        if ($user->rol === UserRole::ADMIN->value && (!isset($validated['id_c']) || !$user->belongsToCine((int) $validated['id_c']))) {
            return response()->json(['message' => 'No puede crear salas en un cine no asignado.'], 403);
        }
        return response()->json(new SalaResource(Sala::create($validated)), 201);
    }

    public function update(StoreSalaRequest $request, int $id): JsonResponse
    {
        $sala = Sala::find($id);
        if (!$sala) return response()->json(['message' => 'Sala no encontrada.'], 404);
        $this->authorize('update', $sala);
        $validated = $request->validated();
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();
        if ($user->rol === UserRole::ADMIN->value && isset($validated['id_c']) && !$user->belongsToCine((int) $validated['id_c'])) {
            return response()->json(['message' => 'No puede mover salas a un cine no asignado.'], 403);
        }
        $sala->update($validated);
        return response()->json(null, 204);
    }

    public function destroy(int $id): JsonResponse
    {
        $sala = Sala::find($id);
        if (!$sala) return response()->json(['message' => 'Sala no encontrada.'], 404);
        $this->authorize('delete', $sala);
        $sala->delete();
        return response()->json(null, 204);
    }
}
