<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = $this->authentication->attempt($request->validated(), $request->boolean('remember'));
        if ($result === 'invalid_credentials') {
            return back()->withErrors(['email' => 'Email or password was not recognised.'])->onlyInput('email');
        }
        if ($result === 'inactive_academy') {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This academy account is inactive. Contact the system administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        return $user->isSuperAdmin() ? redirect()->route('superadmin.academies') : redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authentication->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
