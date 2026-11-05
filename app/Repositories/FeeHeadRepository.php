<?php

namespace App\Repositories;

use App\Models\FeeHead;

class FeeHeadRepository
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): FeeHead
    {
        return FeeHead::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(string $scope, int $id, array $attributes): void
    {
        FeeHead::query()->where('scope', $scope)->findOrFail($id)->update($attributes);
    }

    public function delete(string $scope, int $id): void
    {
        FeeHead::query()->where('scope', $scope)->findOrFail($id)->delete();
    }
}
