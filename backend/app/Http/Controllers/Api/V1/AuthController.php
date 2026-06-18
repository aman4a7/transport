<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Shared\Traits\HasApiResponse;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request)
    {
        $user = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return $this->respond(
            data: $this->userResponse($user),
            message: 'Login successful.',
        );
    }

    public function logout()
    {
        $this->authService->logout();

        return $this->respond(message: 'Logged out successfully.');
    }

    public function me()
    {
        $user = $this->authService->me();

        return $this->respond(
            data: $this->userResponse($user),
        );
    }

    private function userResponse($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->relationLoaded('roles')
                ? $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permissions' => $role->relationLoaded('permissions')
                        ? $role->permissions->pluck('slug')
                        : [],
                ])
                : [],
        ];
    }
}
