<?php

namespace App\Services;

use App\Mail\AcademyChangeRequestReceived;
use App\Mail\ProfileActionLink;
use App\Models\User;
use App\Repositories\ProfileRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class ProfileService
{
    public function __construct(private readonly ProfileRepository $profiles) {}

    /** @return array{user: User, changeRequests: Collection} */
    public function show(User $user): array
    {
        $user->load('academy');
        $changeRequests = $user->role === 'admin' ? $this->profiles->recentChangeRequests((int) $user->academy_id) : collect();

        return compact('user', 'changeRequests');
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): void
    {
        if ($user->isSuperAdmin()) {
            $oldPhotoPath = $user->photo_path;
            if (isset($data['photo'])) {
                $data['photo_path'] = $data['photo']->store('profile-photos', 'public');
            }
            unset($data['photo']);
            if (blank($data['new_password'] ?? null)) {
                unset($data['new_password']);
            } else {
                $data['password'] = $data['new_password'];
                unset($data['new_password']);
            }
            $this->profiles->updateUser($user, $data);
            if (isset($data['photo_path']) && $oldPhotoPath !== null) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return;
        }

        $this->profiles->updateAcademyNameAndAddress($user, $data['academy_name'], $data['address'] ?? null);
    }

    /** @param array{category: string, subject: string, details: string} $data */
    public function requestAcademyChange(User $user, array $data): void
    {
        $changeRequest = $this->profiles->createChangeRequest([
            'academy_id' => $user->academy_id,
            'requested_by' => $user->id,
            ...$data,
        ]);

        try {
            foreach ($this->profiles->superAdministrators() as $superAdmin) {
                Mail::to($superAdmin)->send(new AcademyChangeRequestReceived($changeRequest));
            }
        } catch (Throwable $exception) {
            Log::warning('Academy change request email could not be sent.', ['request_id' => $changeRequest->id, 'exception' => $exception->getMessage()]);
        }
    }

    /** @param array{current_password: string, new_password: string} $data */
    public function sendPasswordChangeVerification(User $user, array $data): string
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            return 'current_password_invalid';
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
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            Log::error('Password change verification email could not be sent.', ['user_id' => $user->id, 'exception' => $exception->getMessage()]);

            return 'email_failed';
        }

        return 'sent';
    }

    public function pendingPasswordChange(string $token): User
    {
        $pending = Cache::get($this->passwordVerificationKey($token));
        abort_unless(is_array($pending), 410, 'This password verification link has expired or was already used.');

        return $this->profiles->findUser((int) $pending['user_id']);
    }

    public function confirmPasswordChange(string $token): string
    {
        $pending = Cache::pull($this->passwordVerificationKey($token));
        abort_unless(is_array($pending), 410, 'This password verification link has expired or was already used.');
        $user = $this->profiles->findUser((int) $pending['user_id']);
        abort_unless($user->role === 'admin' && $user->academy?->is_active, 403);
        $this->profiles->updateUser($user, ['password' => $pending['new_password_hash']]);

        $loginToken = Str::random(64);
        Cache::put($this->loginLinkKey($loginToken), ['user_id' => $user->id], now()->addMinutes(15));
        $loginUrl = URL::temporarySignedRoute('profile.login-link.show', now()->addMinutes(15), ['token' => $loginToken]);
        try {
            Mail::to($user)->send(new ProfileActionLink($loginUrl, 'Your password is updated', 'Use this one-time sign-in link to enter MSZ ERP. It expires in 15 minutes.'));
        } catch (Throwable $exception) {
            $this->profiles->updateUser($user, ['password' => $pending['old_password_hash']]);
            Cache::forget($this->loginLinkKey($loginToken));
            Log::error('Post-change sign-in email could not be sent.', ['user_id' => $user->id, 'exception' => $exception->getMessage()]);

            return 'email_failed';
        }

        return 'sent';
    }

    public function pendingLoginLink(string $token): User
    {
        $pending = Cache::get($this->loginLinkKey($token));
        abort_unless(is_array($pending), 410, 'This sign-in link has expired or was already used.');

        return $this->profiles->findUser((int) $pending['user_id']);
    }

    public function consumeLoginLink(string $token): User
    {
        $pending = Cache::pull($this->loginLinkKey($token));
        abort_unless(is_array($pending), 410, 'This sign-in link has expired or was already used.');
        $user = $this->profiles->findUser((int) $pending['user_id']);
        abort_unless($user->role === 'admin' && $user->academy?->is_active, 403);

        return $user;
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
