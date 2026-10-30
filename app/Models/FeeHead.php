<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeHead extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['name', 'scope', 'class_id', 'student_id', 'amount', 'active'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'active' => 'boolean'];
    }

    public function academyClass(): BelongsTo
    {
        return $this->belongsTo(AcademyClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
