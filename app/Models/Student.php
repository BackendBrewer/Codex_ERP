<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['admission_no', 'name', 'father_name', 'guardian_phone', 'gender', 'date_of_birth', 'class_id', 'admission_date', 'monthly_fee', 'status', 'address'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'admission_date' => 'date', 'monthly_fee' => 'decimal:2'];
    }

    public function academyClass(): BelongsTo
    {
        return $this->belongsTo(AcademyClass::class, 'class_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
