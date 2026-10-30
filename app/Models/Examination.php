<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examination extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['title', 'class_id', 'subject', 'exam_date', 'total_marks', 'passing_marks'];

    protected function casts(): array
    {
        return ['exam_date' => 'date', 'total_marks' => 'decimal:2', 'passing_marks' => 'decimal:2'];
    }

    public function academyClass(): BelongsTo
    {
        return $this->belongsTo(AcademyClass::class, 'class_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
