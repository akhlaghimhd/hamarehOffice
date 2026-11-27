<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\User;
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

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('materio')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
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
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user, $request->header('X-Company-Id')),
            'companies' => $this->companiesFor($user->id),
        ]);
    }

    private function userPayload(User $user, mixed $companyId = null): array
    {
        $access = $this->accessFor($user->id, $companyId ? (int) $companyId : null);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $access['roles'],
            'permissions' => $access['permissions'],
        ];
    }

    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    private function accessFor(int $userId, ?int $companyId = null): array
    {
        $q = CompanyUser::query()
            ->with(['roles.permissions'])
            ->where('user_id', $userId)
            ->where('is_active', true);

        if ($companyId) {
            $q->where('company_id', $companyId);
        } else {
            $q->orderByDesc('is_default');
        }

        $membership = $q->first();
        if (! $membership) {
            return ['roles' => [], 'permissions' => []];
        }

        $roles = [];
        $permissions = [];
        foreach ($membership->roles as $role) {
            $roles[] = $role->name;
            foreach ($role->permissions as $perm) {
                $permissions[] = $perm->name;
            }
        }

        return [
            'roles' => array_values(array_unique($roles)),
            'permissions' => array_values(array_unique($permissions)),
        ];
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
