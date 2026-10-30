<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToAcademy
{
    protected static function bootBelongsToAcademy(): void
    {
        static::addGlobalScope('academy', function (Builder $builder): void {
            if (app()->runningInConsole() && ! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if (! $user instanceof User || $user->role !== 'admin' || $user->academy_id === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->getModel()->qualifyColumn('academy_id'), $user->academy_id);
        });

        static::creating(function (Model $model): void {
            if (app()->runningInConsole() && ! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if (! $user instanceof User || $user->role !== 'admin' || $user->academy_id === null) {
                throw new AuthorizationException('Academy records can only be created inside an academy account.');
            }

            $model->setAttribute('academy_id', $user->academy_id);
        });
    }
}
