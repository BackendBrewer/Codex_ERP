<?php

namespace App\Repositories;

use App\Models\AcademyChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProfileRepository
{
    /** @return Collection<int, AcademyChangeRequest> */
    public function recentChangeRequests(int $academyId): Collection
    {
        return AcademyChangeRequest::query()->where('academy_id', $academyId)->latest()->limit(10)->get();
    }

    /** @param array<string, mixed> $attributes */
    public function updateUser(User $user, array $attributes): void
    {
        $user->update($attributes);
    }

    public function updateAcademyNameAndAddress(User $user, string $name, ?string $address): void
    {
        $user->academy->update(['name' => $name, 'slug' => $this->uniqueAcademySlug($user, $name), 'address' => $address]);
    }

    /** @param array<string, mixed> $attributes */
    public function createChangeRequest(array $attributes): AcademyChangeRequest
    {
        return AcademyChangeRequest::query()->create($attributes)->load(['academy', 'requester']);
    }

    /** @return Collection<int, User> */
    public function superAdministrators(): Collection
    {
        return User::query()->where('role', 'super_admin')->get();
    }

    public function findUser(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    private function uniqueAcademySlug(User $user, string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'academy';
        $slug = $baseSlug;
        $suffix = 2;
        while ($user->academy->newQuery()->where('slug', $slug)->whereKeyNot($user->academy_id)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
