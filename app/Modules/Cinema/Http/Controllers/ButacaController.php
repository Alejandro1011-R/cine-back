<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Controllers;

use App\Modules\Cinema\Http\Requests\StoreButacaRequest;
use App\Modules\Cinema\Http\Resources\ButacaResource;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Identity\Domain\Enums\UserRole;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class ButacaController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Butaca::class);

        $perPage = min($request->integer('per_page', 15), 100);
        $query = Butaca::with('sala');
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();
        if (in_array($user->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)) {
            $query->whereHas('sala.cine.staff', fn ($staff) => $staff->where('usuarios.ci', $user->ci));
        }

        return ButacaResource::collection($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $b = Butaca::find($id);
        if ($b) {
            $this->authorize('view', $b);
        }
        return $b ? response()->json(new ButacaResource($b)) : response()->json(['message' => 'Butaca no encontrada.'], 404);
    }

    public function store(StoreButacaRequest $request): JsonResponse
    {
        $this->authorize('create', Butaca::class);
        $sala = Sala::find((int) $request->validated('id_s'));
        if ($sala === null) {
            return response()->json(['message' => 'Sala no encontrada.'], 404);
        }
        $this->authorize('update', $sala);

        return response()->json(new ButacaResource(Butaca::create($request->validated())), 201);
    }

    public function update(StoreButacaRequest $request, int $id): JsonResponse
    {
        $b = Butaca::find($id);
        if (!$b) return response()->json(['message' => 'Butaca no encontrada.'], 404);
        $this->authorize('update', $b);
        $sala = Sala::find((int) $request->validated('id_s'));
        if ($sala === null) {
            return response()->json(['message' => 'Sala no encontrada.'], 404);
        }
        $this->authorize('update', $sala);
        $b->update($request->validated());
        return response()->json(null, 204);
    }

    public function destroy(int $id): JsonResponse
    {
        $b = Butaca::find($id);
        if (!$b) return response()->json(['message' => 'Butaca no encontrada.'], 404);
        $this->authorize('delete', $b);
        $b->delete();
        return response()->json(null, 204);
    }
}
