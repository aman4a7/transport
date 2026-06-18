<?php

namespace App\Domain\Auth\Services;

use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function login(string $email, string $password): User
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            throw new BusinessRuleException(
                message: 'Invalid email or password.',
            );
        }

        $user = Auth::user();
        request()->session()->regenerate();

        $user->loadMissing([
            'roles:id,name,slug',
            'roles.permissions:id,name,slug',
        ]);

        $this->auditLog->logSensitive(
            action: 'login',
            subject: $user,
            description: "User {$user->email} logged in",
        );

        return $user;
    }

    public function logout(): void
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLog->logSensitive(
                action: 'logout',
                subject: $user,
                description: "User {$user->email} logged out",
            );
        }

        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    public function sendPasswordResetLink(string $email): void
    {
        $status = Password::broker()->sendResetLink(['email' => $email]);

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->auditLog->log(
                action: 'forgot_password',
                subject: $user,
                description: "Password reset requested for {$email}",
            );
        }

        if ($status !== Password::RESET_LINK_SENT) {
            throw new BusinessRuleException(
                message: 'Unable to send reset link. Please try again later.',
                code: 500,
            );
        }
    }

    public function me(): User
    {
        $user = Auth::user();

        if (! $user) {
            throw new BusinessRuleException(
                message: 'Not authenticated.',
                code: 401,
            );
        }

        $user->loadMissing([
            'roles:id,name,slug',
            'roles.permissions:id,name,slug',
        ]);

        return $user;
    }
}
