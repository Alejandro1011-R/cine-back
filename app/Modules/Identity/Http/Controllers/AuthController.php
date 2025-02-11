<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Application\Actions\LoginUserAction;
use App\Modules\Identity\Application\DTOs\LoginData;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Resources\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUserAction $loginAction,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->handle(new LoginData(
            ci: $request->validated('ci'),
            contrasena: $request->validated('contrasena'),
        ));

        if ($result === null) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        return response()->json([
            'usuario' => new UsuarioResource($result['usuario']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }
}
