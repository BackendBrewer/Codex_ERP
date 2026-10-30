<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['name', 'guardian_name', 'phone', 'class_id', 'status', 'inquiry_date', 'notes'];

    protected function casts(): array
    {
        return ['inquiry_date' => 'date'];
    }

    public function academyClass(): BelongsTo
    {
        return $this->belongsTo(AcademyClass::class, 'class_id');
    }
}
