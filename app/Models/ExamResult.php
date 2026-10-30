<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['examination_id', 'student_id', 'marks_obtained', 'remarks'];

    protected function casts(): array
    {
        return ['marks_obtained' => 'decimal:2'];
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
