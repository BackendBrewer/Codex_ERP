<?php

namespace App\Repositories;

use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StudentWorkspaceRepository
{
    public function findStudent(int $id): Student
    {
        return Student::query()->with('academyClass')->findOrFail($id);
    }

    public function invoices(Student $student): LengthAwarePaginator
    {
        return $student->invoices()->withSum('payments as paid_total', 'amount')->withCount('payments')
            ->latest('invoice_month')->paginate(20)->withQueryString();
    }

    public function payments(Student $student): Collection
    {
        return FeePayment::query()->where('student_id', $student->id)
            ->latest('paid_at')->limit(10)->get();
    }

    public function invoiceSummary(Student $student): ?object
    {
        $paidTotal = '(SELECT COALESCE(SUM(fee_payments.amount), 0) FROM fee_payments WHERE fee_payments.fee_invoice_id = fee_invoices.id)';
        $invoiceDue = '(fee_invoices.amount - fee_invoices.discount + fee_invoices.late_fee)';

        return $student->invoices()->selectRaw(
            'COALESCE(SUM(CASE WHEN fee_invoices.status != ? THEN '.$invoiceDue.' ELSE 0 END), 0) AS billed_total, '
            .'COALESCE(SUM('.$paidTotal.'), 0) AS paid_total, '
            .'COALESCE(SUM(CASE WHEN fee_invoices.status != ? AND '.$invoiceDue.' > '.$paidTotal.' THEN '.$invoiceDue.' - '.$paidTotal.' ELSE 0 END), 0) AS due_total, '
            .'COALESCE(SUM(CASE WHEN fee_invoices.status != ? AND '.$invoiceDue.' > '.$paidTotal.' THEN 1 ELSE 0 END), 0) AS open_count',
            ['waived', 'waived', 'waived'],
        )->first();
    }

    public function attendanceSummary(Student $student): Collection
    {
        return $student->attendanceRecords()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
    }

    public function attendanceRecords(Student $student): Collection
    {
        return $student->attendanceRecords()->orderByDesc('attendance_date')->limit(30)->get();
    }

    public function examResults(Student $student): Collection
    {
        return $student->examResults()->with('examination.academyClass')->latest()->get();
    }
}
