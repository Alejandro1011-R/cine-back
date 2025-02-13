<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreActorRequest;
use App\Modules\Catalog\Http\Resources\ActorResource;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Actor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class ActorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:SuperAdmin,Admin')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('per_page', 15), 100);
        return ActorResource::collection(Actor::paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $actor = Actor::find($id);
        if ($actor === null) {
            return response()->json(['message' => 'Actor no encontrado.'], 404);
        }
        return response()->json(new ActorResource($actor));
    }

    public function store(StoreActorRequest $request): JsonResponse
    {
        $actor = Actor::create($request->validated());
        return response()->json(new ActorResource($actor), 201);
    }

    public function update(StoreActorRequest $request, int $id): JsonResponse
    {
        $actor = Actor::find($id);
        if ($actor === null) {
            return response()->json(['message' => 'Actor no encontrado.'], 404);
        }

        $actor->update($request->validated());
        return response()->json(null, 204);
    }

    public function destroy(int $id): JsonResponse
    {
        $actor = Actor::find($id);
        if ($actor === null) {
            return response()->json(['message' => 'Actor no encontrado.'], 404);
        }
        $actor->delete();
        return response()->json(null, 204);
    }
}
