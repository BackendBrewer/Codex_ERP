<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPasswordChangeVerificationRequest;
use App\Http\Requests\StoreAcademyChangeRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function show(Request $request): View
    {
        return view('profile.show', $this->profiles->show($request->user()));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->profiles->update($user, $request->validated());

        return back()->with('success', $user->isSuperAdmin() ? 'Your Super Admin profile has been updated.' : 'Academy name and address have been updated. Other academy settings need Super Admin approval.');
    }

    public function requestAcademyChange(StoreAcademyChangeRequest $request): RedirectResponse
    {
        $this->profiles->requestAcademyChange($request->user(), $request->validated());

        return back()->with('success', 'Your request has been sent to the Super Admin for review.');
    }

    public function sendPasswordChangeVerification(SendPasswordChangeVerificationRequest $request): RedirectResponse
    {
        $result = $this->profiles->sendPasswordChangeVerification($request->user(), $request->validated());
        if ($result === 'current_password_invalid') {
            return back()->withErrors(['current_password' => 'The current password does not match our records.']);
        }
        if ($result === 'email_failed') {
            return back()->withErrors(['new_password' => 'We could not send the verification email. Check the mail setup and try again.']);
        }

        return back()->with('success', 'Check your academy admin email to verify this password change. Your current password stays active until you confirm.');
    }

    public function showPasswordChangeConfirmation(string $token): View
    {
        return view('profile.confirm-password-change', ['user' => $this->profiles->pendingPasswordChange($token)]);
    }

    public function confirmPasswordChange(Request $request, string $token): RedirectResponse
    {
        $currentUserId = Auth::id();
        if ($this->profiles->confirmPasswordChange($token) === 'email_failed') {
            return redirect()->route('login')->withErrors(['email' => 'The sign-in email could not be sent, so the password change was cancelled. Please try again later.']);
        }
        if ($currentUserId !== null && Auth::id() === $currentUserId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Email verified. Use the one-time sign-in link we just emailed you.');
    }

    public function showLoginLink(string $token): View
    {
        return view('profile.confirm-login', ['user' => $this->profiles->pendingLoginLink($token)]);
    }

    public function consumeLoginLink(Request $request, string $token): RedirectResponse
    {
        $user = $this->profiles->consumeLoginLink($token);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'You are signed in securely.');
    }
}
