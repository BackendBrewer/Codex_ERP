<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Academy extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'contact_email', 'contact_phone', 'city', 'province', 'address', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function administrator(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'admin');
    }
}
