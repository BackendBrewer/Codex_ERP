<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWithOpenBalanceSummary(Builder $query): Builder
    {
        $paidTotal = '(SELECT COALESCE(SUM(fee_payments.amount), 0) FROM fee_payments WHERE fee_payments.fee_invoice_id = fee_invoices.id)';
        $balance = '(fee_invoices.amount - fee_invoices.discount + fee_invoices.late_fee - '.$paidTotal.')';

        return $query->whereIn('fee_invoices.status', ['unpaid', 'partial'])
            ->selectRaw('COUNT(*) AS open_count, COALESCE(SUM(CASE WHEN '.$balance.' > 0 THEN '.$balance.' ELSE 0 END), 0) AS outstanding_total');
    }
}
