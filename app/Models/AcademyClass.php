<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyClass extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['name', 'section', 'program', 'monthly_fee', 'capacity', 'active'];

    protected function casts(): array
    {
        return ['monthly_fee' => 'decimal:2', 'active' => 'boolean'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'class_id');
    }
}
