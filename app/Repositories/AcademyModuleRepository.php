<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcademyModuleRepository
{
    /** @param class-string<Model> $modelClass
     * @param  list<string>  $relations
     */
    public function listingQuery(string $modelClass, array $relations): Builder
    {
        return $modelClass::query()->with($relations);
    }

    /** @param class-string<Model> $modelClass */
    public function find(string $modelClass, int $id): Model
    {
        return $modelClass::query()->findOrFail($id);
    }

    /** @param class-string<Model> $modelClass
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $modelClass, array $attributes): Model
    {
        return $modelClass::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Model $record, array $attributes): void
    {
        $record->update($attributes);
    }

    public function delete(Model $record): void
    {
        $record->delete();
    }
}
