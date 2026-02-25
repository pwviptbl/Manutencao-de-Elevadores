<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Rate limiting: 5 tentativas por minuto por IP
        $key = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ["Muitas tentativas. Tente novamente em {$seconds} segundos."],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Usuário inativo.'],
            ]);
        }

        RateLimiter::clear($key);

        $token = $user->createToken('api', expiresAt: now()->addHours(8))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'roles'  => $user->getRoleNames(),
                'tenant' => [
                    'id'   => $user->tenant->id,
                    'name' => $user->tenant->name,
                    'plan' => $user->tenant->plan,
                ],
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoga apenas o token atual
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * POST /api/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Revoga todos os tokens do usuário
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout global realizado.']);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant');

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'avatar_url'  => $user->avatar_url,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'tenant'      => [
                'id'       => $user->tenant->id,
                'name'     => $user->tenant->name,
                'slug'     => $user->tenant->slug,
                'plan'     => $user->tenant->plan,
                'settings' => $user->tenant->settings,
            ],
        ]);
    }
}
