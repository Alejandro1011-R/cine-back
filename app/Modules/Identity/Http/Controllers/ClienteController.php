<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Http\Resources\ClienteResource;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class ClienteController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Cliente::class);

        $perPage = min($request->integer('per_page', 15), 100);
        $query = Cliente::query();
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Models\Usuario $user */
        $user = $request->user();

        if ($user->rol === UserRole::ADMIN->value) {
            $query->whereExists(function ($subQuery) use ($user): void {
                $subQuery->selectRaw('1')
                    ->from('compras')
                    ->join('sesiones', function ($join): void {
                        $join->on('compras.id_p', '=', 'sesiones.id_p')
                            ->on('compras.id_s', '=', 'sesiones.id_s')
                            ->on('compras.fecha', '=', 'sesiones.fecha');
                    })
                    ->join('salas', 'sesiones.id_s', '=', 'salas.id_s')
                    ->join('cine_usuario', 'salas.id_c', '=', 'cine_usuario.id_c')
                    ->whereColumn('compras.ci', 'clientes.ci')
                    ->where('cine_usuario.ci', $user->ci);
            });
        }

        return ClienteResource::collection($query->paginate($perPage));
    }

    public function show(string $ci): JsonResponse
    {
        $cliente = Cliente::find($ci);

        if ($cliente === null) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $this->authorize('view', $cliente);

        return response()->json(new ClienteResource($cliente));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Cliente::class);

        $validated = $request->validate([
            'ci' => ['required', 'string', 'size:11'],
            'correo' => ['nullable', 'string', 'email', 'max:256'],
        ]);

        try {
            $cliente = Cliente::create([
                'ci' => $validated['ci'],
                'correo' => $validated['correo'] ?? null,
                'confiabilidad' => true,
            ]);
            return response()->json(new ClienteResource($cliente), 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23505) {
                return response()->json(['message' => 'El cliente ya existe.'], 409);
            }
            throw $e;
        }
    }

    public function update(Request $request, string $ci): JsonResponse
    {
        $validated = $request->validate([
            'ci' => ['required', 'string', 'size:11'],
            'correo' => ['nullable', 'string', 'email', 'max:256'],
            'confiabilidad' => ['nullable', 'boolean'],
        ]);

        if ($ci !== $validated['ci']) {
            return response()->json(['message' => 'CI no coincide.'], 400);
        }

        $cliente = Cliente::find($ci);
        if ($cliente === null) {
            return response()->json(['message' => 'Cliente no encontrado.'], 400);
        }

        $this->authorize('update', $cliente);

        $cliente->correo = $validated['correo'] ?? $cliente->correo;
        $cliente->confiabilidad = $validated['confiabilidad'] ?? $cliente->confiabilidad;
        $cliente->save();

        return response()->json(null, 204);
    }

    public function destroy(string $ci): JsonResponse
    {
        $cliente = Cliente::find($ci);

        if ($cliente === null) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $this->authorize('delete', $cliente);

        $cliente->delete();

        return response()->json(null, 204);
    }
}
