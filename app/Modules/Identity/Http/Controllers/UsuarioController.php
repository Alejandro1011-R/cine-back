<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\ChangeUserRoleAction;
use App\Modules\Identity\Application\Actions\RegisterUserAction;
use App\Modules\Identity\Application\Actions\UpdateUserAction;
use App\Modules\Identity\Application\DTOs\RegisterUserData;
use App\Modules\Identity\Application\DTOs\UpdateUserData;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Http\Requests\RegisterUserRequest;
use App\Modules\Identity\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Http\Resources\UsuarioResource;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class UsuarioController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RegisterUserAction $registerAction,
        private readonly UpdateUserAction $updateAction,
        private readonly ChangeUserRoleAction $changeRoleAction,
    ) {
        $this->middleware('auth:sanctum')->except(['store']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Usuario::class);

        $perPage = min($request->integer('per_page', 15), 100);
        $role = $request->query('rol');

        if ($role !== null && UserRole::tryFrom((string) $role) === null) {
            abort(422, 'Rol inválido.');
        }

        /** @var Usuario $user */
        $user = $request->user();
        $query = Usuario::with(['cliente', 'cines']);

        if ($role !== null) {
            $query->where('rol', $role);
        }

        if ($user->rol === UserRole::ADMIN->value) {
            $query->whereIn('rol', [
                UserRole::ADMIN->value,
                UserRole::TAQUILLERO->value,
            ])->whereHas('cines', function ($cines) use ($user): void {
                $cines->whereIn('cines.id_c', $user->cines()->select('cines.id_c'));
            });
        }

        $usuarios = $query->paginate($perPage);
        return UsuarioResource::collection($usuarios);
    }

    public function show(string $ci): JsonResponse
    {
        $usuario = Usuario::with(['cliente', 'cines'])->find($ci);

        if ($usuario === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $this->authorize('view', $usuario);

        return response()->json(new UsuarioResource($usuario));
    }

    public function store(RegisterUserRequest $request): JsonResponse
    {
        $data = new RegisterUserData(
            ci: $request->validated('ci'),
            nombreS: $request->validated('nombre_s'),
            apellidos: $request->validated('apellidos'),
            correo: $request->validated('correo'),
            contrasena: $request->validated('contrasena'),
        );

        try {
            $usuario = $this->registerAction->handle($data);
            return response()->json(new UsuarioResource($usuario), 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23505) { // PostgreSQL unique violation
                return response()->json(['message' => 'El usuario ya existe.'], 409);
            }
            throw $e;
        }
    }

    public function update(UpdateUserRequest $request, string $ci): JsonResponse
    {
        $usuario = Usuario::find($ci);

        if ($usuario === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $this->authorize('update', $usuario);

        if ($ci !== $request->validated('ci')) {
            return response()->json(['message' => 'CI no coincide.'], 400);
        }

        $data = new UpdateUserData(
            nombreS: $request->validated('nombre_s'),
            apellidos: $request->validated('apellidos'),
            correo: $request->validated('correo'),
            contrasena: $request->validated('contrasena'),
        );

        $usuario = $this->updateAction->handle($ci, $data);

        if ($usuario === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 400);
        }

        return response()->json(null, 204);
    }

    public function promoteSocio(string $ci): JsonResponse
    {
        $target = Usuario::find($ci);

        if ($target === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $this->authorize('promoteSocio', $target);

        /** @var Usuario $actor */
        $actor = request()->user();
        $usuario = $this->changeRoleAction->handle($ci, UserRole::SOCIO->value, $actor->ci);

        if ($usuario === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return response()->json(null, 204);
    }

    public function changeRole(string $ci, string $rol): JsonResponse
    {
        $this->authorize('changeRole', Usuario::class);

        if (UserRole::tryFrom($rol) === null) {
            return response()->json(['message' => 'Rol inválido.'], 422);
        }

        /** @var Usuario $actor */
        $actor = request()->user();
        $usuario = $this->changeRoleAction->handle($ci, $rol, $actor->ci);

        if ($usuario === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return response()->json(null, 204);
    }

    public function destroy(string $ci): JsonResponse
    {
        $usuario = Usuario::find($ci);

        if ($usuario === null) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $this->authorize('delete', $usuario);

        $usuario->delete();

        return response()->json(null, 204);
    }
}
