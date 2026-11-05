<?php

namespace App\Repositories;

use App\Models\AccountEntry;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Inquiry;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository
{
    /** @return Collection<int, FeePayment> */
    public function recentPayments(): Collection
    {
        return FeePayment::query()->with(['student.academyClass'])->latest('paid_at')->limit(8)->get();
    }

    /** @return Collection<int, FeePayment> */
    public function paymentsBetween(Carbon $start, Carbon $end): Collection
    {
        return FeePayment::query()->whereBetween('paid_at', [$start, $end])->get(['amount', 'paid_at']);
    }

    /** @return Collection<string, int> */
    public function attendanceByStatusToday(): Collection
    {
        return AttendanceRecord::query()->whereDate('attendance_date', Carbon::today())->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
    }

    public function activeStudentCount(): int
    {
        return Student::query()->where('status', 'active')->count();
    }

    public function collectedBetween(Carbon $start, Carbon $end): float
    {
        return (float) FeePayment::query()->whereBetween('paid_at', [$start, $end])->sum('amount');
    }

    public function outstandingBalance(): float
    {
        return (float) (FeeInvoice::query()->withOpenBalanceSummary()->value('outstanding_total') ?? 0);
    }

    public function openInquiryCount(): int
    {
        return Inquiry::query()->whereIn('status', ['new', 'contacted', 'visit'])->count();
    }

    public function accountTotal(string $type, Carbon $start, Carbon $end): float
    {
        return (float) AccountEntry::query()->where('type', $type)->whereBetween('entry_date', [$start, $end])->sum('amount');
    }
}
