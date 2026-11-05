<?php

namespace App\Repositories;

use App\Models\AcademyChangeRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AcademyChangeRequestRepository
{
    public function latestWithParties(): LengthAwarePaginator
    {
        return AcademyChangeRequest::query()->with(['academy', 'requester', 'reviewer'])->latest()->paginate(20);
    }

    public function countOpen(): int
    {
        return AcademyChangeRequest::query()->whereIn('status', ['open', 'in_review'])->count();
    }

    /** @param array<string, mixed> $attributes */
    public function update(AcademyChangeRequest $request, array $attributes): void
    {
        $request->update($attributes);
    }
}
