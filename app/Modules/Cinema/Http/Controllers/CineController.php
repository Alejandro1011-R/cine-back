<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Controllers;

use App\Modules\Cinema\Http\Requests\StoreCineRequest;
use App\Modules\Cinema\Http\Requests\UpdateCineRequest;
use App\Modules\Cinema\Http\Resources\CineResource;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Shared\Infrastructure\Audit\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class CineController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Cine::class);

        $perPage = min($request->integer('per_page', 15), 100);
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();

        $query = Cine::with('salas');

        if ($user->rol === UserRole::ADMIN->value) {
            $query->whereHas('staff', function ($staff) use ($user): void {
                $staff->where('usuarios.ci', $user->ci);
            });
        }

        return CineResource::collection($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $cine = Cine::with(['salas.butacas', 'salas.sesiones'])->findOrFail($id);
        $this->authorize('view', $cine);

        return response()->json(new CineResource($cine));
    }

    public function store(StoreCineRequest $request): JsonResponse
    {
        $this->authorize('create', Cine::class);

        $cine = Cine::create($request->validated());
        return response()->json(new CineResource($cine), 201);
    }

    public function update(UpdateCineRequest $request, int $id): JsonResponse
    {
        $cine = Cine::findOrFail($id);
        $this->authorize('update', $cine);

        $cine->update($request->validated());

        return response()->json(new CineResource($cine));
    }

    public function destroy(int $id): JsonResponse
    {
        $cine = Cine::findOrFail($id);
        $this->authorize('delete', $cine);

        $cine->delete();
        return response()->json(null, 204);
    }

    public function assignStaff(Request $request, int $id): JsonResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();
        if ($actor->rol !== UserRole::SUPER_ADMIN->value) {
            abort(403);
        }

        $validated = $request->validate([
            'ci' => ['required', 'string', 'size:11'],
        ]);

        $cine = Cine::findOrFail($id);
        $staff = Usuario::find($validated['ci']);
        if ($staff === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if (!in_array($staff->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)) {
            return response()->json(['message' => 'Solo Admin o Taquillero pueden asignarse a cines.'], 422);
        }

        $cine->staff()->syncWithoutDetaching([$staff->ci]);
        $this->auditLogger->record(
            actorCi: $actor->ci,
            action: 'cinema.staff.assigned',
            auditableType: Cine::class,
            auditableId: (string) $cine->id_c,
            metadata: ['staff_ci' => $staff->ci, 'staff_role' => $staff->rol],
        );

        return response()->json(new CineResource($cine->load('staff')), 200);
    }

    public function detachStaff(Request $request, int $id, string $ci): JsonResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();
        if ($actor->rol !== UserRole::SUPER_ADMIN->value) {
            abort(403);
        }

        $cine = Cine::findOrFail($id);
        $staff = Usuario::find($ci);
        if ($staff === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $cine->staff()->detach($staff->ci);
        $this->auditLogger->record(
            actorCi: $actor->ci,
            action: 'cinema.staff.detached',
            auditableType: Cine::class,
            auditableId: (string) $cine->id_c,
            metadata: ['staff_ci' => $staff->ci, 'staff_role' => $staff->rol],
        );

        return response()->json(null, 204);
    }
}
