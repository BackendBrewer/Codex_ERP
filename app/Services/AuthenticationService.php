<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AuthenticationService
{
    /** @param array{email: string, password: string} $credentials */
    public function attempt(array $credentials, bool $remember): string
    {
        if (! Auth::attempt($credentials, $remember)) {
            return 'invalid_credentials';
        }

        $user = Auth::user();
        if (! $user->isSuperAdmin() && ($user->role !== 'admin' || $user->academy === null || ! $user->academy->is_active)) {
            Auth::logout();

            return 'inactive_academy';
        }

        return 'authenticated';
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
