<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Application\Actions\CreatePeliculaAction;
use App\Modules\Catalog\Application\Actions\UpdatePeliculaAction;
use App\Modules\Catalog\Application\DTOs\PeliculaData;
use App\Modules\Catalog\Http\Requests\StorePeliculaRequest;
use App\Modules\Catalog\Http\Resources\PeliculaResource;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class PeliculaController extends Controller
{
    public function __construct(
        private readonly CreatePeliculaAction $createAction,
        private readonly UpdatePeliculaAction $updateAction,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('role:SuperAdmin,Admin')->only(['store', 'update']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('per_page', 15), 100);
        return PeliculaResource::collection(Pelicula::with(['actores', 'generos'])->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $pelicula = Pelicula::with(['actores', 'generos'])->find($id);

        if ($pelicula === null) {
            return response()->json(['message' => 'Película no encontrada.'], 404);
        }

        return response()->json(new PeliculaResource($pelicula));
    }

    public function store(StorePeliculaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = new PeliculaData(
            sinopsis: $validated['sinopsis'] ?? null,
            anno: $validated['anno'] ?? null,
            nacionalidad: $validated['nacionalidad'] ?? null,
            duracion: $validated['duracion'] ?? null,
            titulo: $validated['titulo'] ?? null,
            imagen: $validated['imagen'] ?? null,
            trailer: $validated['trailer'] ?? null,
            actorIds: $validated['actor_ids'] ?? [],
            generoIds: $validated['genero_ids'] ?? [],
        );

        $pelicula = $this->createAction->handle($data);

        return response()->json(new PeliculaResource($pelicula), 201);
    }

    public function update(StorePeliculaRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $data = new PeliculaData(
            sinopsis: $validated['sinopsis'] ?? null,
            anno: $validated['anno'] ?? null,
            nacionalidad: $validated['nacionalidad'] ?? null,
            duracion: $validated['duracion'] ?? null,
            titulo: $validated['titulo'] ?? null,
            imagen: $validated['imagen'] ?? null,
            trailer: $validated['trailer'] ?? null,
            actorIds: $validated['actor_ids'] ?? [],
            generoIds: $validated['genero_ids'] ?? [],
        );

        $pelicula = $this->updateAction->handle($id, $data);

        if ($pelicula === null) {
            return response()->json(['message' => 'Película no encontrada.'], 404);
        }

        return response()->json(null, 204);
    }
}
