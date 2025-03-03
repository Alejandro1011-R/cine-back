<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Shared\Http\Resources\AuditLogResource;
use App\Shared\Infrastructure\Audit\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Usuario $user */
        $user = $request->user();

        if ($user->rol !== UserRole::SUPER_ADMIN->value) {
            abort(403);
        }

        $query = AuditLog::query()->latest('id');

        if ($request->filled('actor_ci')) {
            $query->where('actor_ci', $request->string('actor_ci')->toString());
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->string('auditable_type')->toString());
        }

        $perPage = min($request->integer('per_page', 15), 100);

        return response()->json(AuditLogResource::collection($query->paginate($perPage))->response()->getData(true));
    }
}
