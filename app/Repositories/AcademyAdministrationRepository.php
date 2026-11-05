<?php

namespace App\Repositories;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AcademyAdministrationRepository
{
    public function listingQuery(string $search, string $sort, string $direction): Builder
    {
        return Academy::query()->with('administrator')->withCount('users')
            ->when($search !== '', fn (Builder $builder) => $builder->where(function (Builder $filter) use ($search): void {
                $filter->where('name', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('province', 'like', '%'.$search.'%')
                    ->orWhere('contact_email', 'like', '%'.$search.'%')
                    ->orWhere('contact_phone', 'like', '%'.$search.'%')
                    ->orWhereHas('administrator', fn (Builder $admin) => $admin->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            }))
            ->when($search === '', fn (Builder $builder) => $builder->whereRaw('1 = 0'))
            ->orderBy($sort, $direction);
    }

    public function activeCount(string $search): int
    {
        return $this->listingQuery($search, 'name', 'asc')->reorder()->where('is_active', true)->count();
    }

    /** @return Collection<int, Academy> */
    public function allChoices(): Collection
    {
        return Academy::query()->with('administrator')->orderBy('name')->get();
    }

    /** @param array<string, mixed> $academyData
     * @param  array<string, mixed>  $administratorData
     */
    public function create(array $academyData, array $administratorData): Academy
    {
        $academy = Academy::query()->create($academyData);
        $academy->users()->create($administratorData);

        return $academy;
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Academy::query()->where('slug', $slug)->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId))->exists();
    }

    /** @param array<string, mixed> $data */
    public function update(Academy $academy, array $data): void
    {
        $academy->update($data);
    }
}
