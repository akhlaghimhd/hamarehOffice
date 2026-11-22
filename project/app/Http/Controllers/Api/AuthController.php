<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['ایمیل یا رمز عبور نادرست است.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('materio')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'companies' => $this->companiesFor($user->id),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'ok']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'companies' => $this->companiesFor($user->id),
        ]);
    }

    private function companiesFor(int $userId): array
    {
        return CompanyUser::query()
            ->with('company:id,name,code,is_active')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_default')
            ->get()
            ->map(fn (CompanyUser $m) => [
                'id' => $m->company_id,
                'name' => $m->company?->name,
                'code' => $m->company?->code,
                'is_default' => $m->is_default,
            ])
            ->values()
            ->all();
    }
}
