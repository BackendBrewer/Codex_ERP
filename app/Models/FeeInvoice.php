<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['invoice_no', 'student_id', 'invoice_month', 'due_date', 'amount', 'discount', 'late_fee', 'status', 'remarks', 'fee_head_details'];

    protected function casts(): array
    {
        return ['invoice_month' => 'date', 'due_date' => 'date', 'amount' => 'decimal:2', 'discount' => 'decimal:2', 'late_fee' => 'decimal:2', 'fee_head_details' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }
}
