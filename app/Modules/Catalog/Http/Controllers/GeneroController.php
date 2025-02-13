<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreGeneroRequest;
use App\Modules\Catalog\Http\Resources\GeneroResource;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Genero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class GeneroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:SuperAdmin,Admin')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('per_page', 15), 100);
        return GeneroResource::collection(Genero::paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $genero = Genero::find($id);
        if ($genero === null) {
            return response()->json(['message' => 'Género no encontrado.'], 404);
        }
        return response()->json(new GeneroResource($genero));
    }

    public function store(StoreGeneroRequest $request): JsonResponse
    {
        $genero = Genero::create($request->validated());
        return response()->json(new GeneroResource($genero), 201);
    }

    public function update(StoreGeneroRequest $request, int $id): JsonResponse
    {
        $genero = Genero::find($id);
        if ($genero === null) {
            return response()->json(['message' => 'Género no encontrado.'], 404);
        }

        $genero->update($request->validated());
        return response()->json(null, 204);
    }

    public function destroy(int $id): JsonResponse
    {
        $genero = Genero::find($id);
        if ($genero === null) {
            return response()->json(['message' => 'Género no encontrado.'], 404);
        }
        $genero->delete();
        return response()->json(null, 204);
    }
}
