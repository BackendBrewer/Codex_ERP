<?php

namespace App\Http\Controllers;

use App\Mail\AcademyChangeRequestReceived;
use App\Mail\ProfileActionLink;
use App\Models\AcademyChangeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('academy');
        $changeRequests = $user->role === 'admin'
            ? AcademyChangeRequest::query()->where('academy_id', $user->academy_id)->latest()->limit(10)->get()
            : collect();

        return view('profile.show', compact('user', 'changeRequests'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'new_password' => ['nullable', 'string', 'min:12', 'confirmed'],
            ]);
            $oldPhotoPath = $user->photo_path;
            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('profile-photos', 'public');
            }
            unset($data['photo']);
            if (blank($data['new_password'] ?? null)) {
                unset($data['new_password']);
            } else {
                $data['password'] = $data['new_password'];
                unset($data['new_password']);
            }
            $user->update($data);
            if ($request->hasFile('photo') && $oldPhotoPath !== null) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return back()->with('success', 'Your Super Admin profile has been updated.');
        }

        abort_unless($user->role === 'admin' && $user->academy !== null, 403);
        $data = $request->validate([
            'academy_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        $academy = $user->academy;
        $baseSlug = Str::slug($data['academy_name']) ?: 'academy';
        $slug = $baseSlug;
        $suffix = 2;
        while ($academy->newQuery()->where('slug', $slug)->whereKeyNot($academy->id)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }
        $academy->update(['name' => $data['academy_name'], 'slug' => $slug, 'address' => $data['address'] ?? null]);

        return back()->with('success', 'Academy name and address have been updated. Other academy settings need Super Admin approval.');
    }

    public function requestAcademyChange(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'admin' && $user->academy_id !== null, 403);
        $data = $request->validate([
            'category' => ['required', Rule::in(['contact', 'branding', 'modules', 'other'])],
            'subject' => ['required', 'string', 'max:180'],
            'details' => ['required', 'string', 'max:3000'],
        ]);
        $changeRequest = AcademyChangeRequest::query()->create([
            'academy_id' => $user->academy_id,
            'requested_by' => $user->id,
            ...$data,
        ])->load(['academy', 'requester']);

        try {
            foreach (User::query()->where('role', 'super_admin')->get() as $superAdmin) {
                Mail::to($superAdmin)->send(new AcademyChangeRequestReceived($changeRequest));
            }
        } catch (\Throwable $exception) {
            Log::warning('Academy change request email could not be sent.', ['request_id' => $changeRequest->id, 'exception' => $exception->getMessage()]);
        }

        return back()->with('success', 'Your request has been sent to the Super Admin for review.');
    }

    public function sendPasswordChangeVerification(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'admin', 403);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:12', 'different:current_password', 'confirmed'],
        ]);
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password does not match our records.']);
        }

        $token = Str::random(64);
        $cacheKey = $this->passwordVerificationKey($token);
        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'new_password_hash' => Hash::make($data['new_password']),
            'old_password_hash' => $user->password,
        ], now()->addMinutes(30));
        $verificationUrl = URL::temporarySignedRoute('profile.password.verify.show', now()->addMinutes(30), ['token' => $token]);

        try {
            Mail::to($user)->send(new ProfileActionLink(
                $verificationUrl,
                'Verify your password change',
                'Click the button to confirm this password change. We will then email you a separate one-time sign-in link.',
            ));
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);
            Log::error('Password change verification email could not be sent.', ['user_id' => $user->id, 'exception' => $exception->getMessage()]);

            return back()->withErrors(['new_password' => 'We could not send the verification email. Check the mail setup and try again.']);
        }

        return back()->with('success', 'Check your academy admin email to verify this password change. Your current password stays active until you confirm.');
    }

    public function showPasswordChangeConfirmation(string $token): View
    {
        $pending = Cache::get($this->passwordVerificationKey($token));
        abort_unless(is_array($pending), 410, 'This password verification link has expired or was already used.');
        $user = User::query()->findOrFail($pending['user_id']);

        return view('profile.confirm-password-change', compact('user'));
    }

    public function confirmPasswordChange(Request $request, string $token): RedirectResponse
    {
        $pending = Cache::pull($this->passwordVerificationKey($token));
        abort_unless(is_array($pending), 410, 'This password verification link has expired or was already used.');
        $user = User::query()->findOrFail($pending['user_id']);
        abort_unless($user->role === 'admin' && $user->academy?->is_active, 403);
        $user->update(['password' => $pending['new_password_hash']]);

        $loginToken = Str::random(64);
        Cache::put($this->loginLinkKey($loginToken), ['user_id' => $user->id], now()->addMinutes(15));
        $loginUrl = URL::temporarySignedRoute('profile.login-link.show', now()->addMinutes(15), ['token' => $loginToken]);
        try {
            Mail::to($user)->send(new ProfileActionLink(
                $loginUrl,
                'Your password is updated',
                'Use this one-time sign-in link to enter MSZ ERP. It expires in 15 minutes.',
            ));
        } catch (\Throwable $exception) {
            $user->update(['password' => $pending['old_password_hash']]);
            Cache::forget($this->loginLinkKey($loginToken));
            Log::error('Post-change sign-in email could not be sent.', ['user_id' => $user->id, 'exception' => $exception->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'The sign-in email could not be sent, so the password change was cancelled. Please try again later.']);
        }

        if (Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Email verified. Use the one-time sign-in link we just emailed you.');
    }

    public function showLoginLink(string $token): View
    {
        $pending = Cache::get($this->loginLinkKey($token));
        abort_unless(is_array($pending), 410, 'This sign-in link has expired or was already used.');
        $user = User::query()->findOrFail($pending['user_id']);

        return view('profile.confirm-login', compact('user'));
    }

    public function consumeLoginLink(Request $request, string $token): RedirectResponse
    {
        $pending = Cache::pull($this->loginLinkKey($token));
        abort_unless(is_array($pending), 410, 'This sign-in link has expired or was already used.');
        $user = User::query()->findOrFail($pending['user_id']);
        abort_unless($user->role === 'admin' && $user->academy?->is_active, 403);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'You are signed in securely.');
    }

    private function passwordVerificationKey(string $token): string
    {
        return 'profile-password-verification:'.hash('sha256', $token);
    }

    private function loginLinkKey(string $token): string
    {
        return 'profile-login-link:'.hash('sha256', $token);
    }
}
