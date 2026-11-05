<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\User;
use App\Repositories\DashboardRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard) {}

    /** @return array{stats: array<string, int|float>, payments: Collection, features: array<string, bool>, collectionHistory: Collection, attendanceToday: Collection} */
    public function dataFor(User $user): array
    {
        $features = [
            'students' => $user->hasFeature('students'),
            'fees' => $user->hasFeature('fees.receipts'),
            'fee_balance' => $user->hasFeature('fees.invoices') || $user->hasFeature('fees.reports.pending'),
            'inquiries' => $user->hasFeature('inquiries'),
            'accounts' => $user->hasFeature('accounts'),
            'reports' => $user->hasFeature('reports.dashboard'),
            'attendance' => $user->hasFeature('attendance'),
        ];
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $payments = $features['fees'] ? $this->dashboard->recentPayments() : collect();
        $collectionHistory = collect();
        if ($features['fees']) {
            $historyStart = Carbon::now()->startOfMonth()->subMonths(5);
            $paymentsByMonth = $this->dashboard->paymentsBetween($historyStart, $monthEnd)
                ->groupBy(fn (FeePayment $payment): string => $payment->paid_at->format('Y-m'));
            for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
                $month = Carbon::now()->startOfMonth()->subMonths($monthOffset);
                $collectionHistory->push([
                    'label' => $month->format('M'),
                    'amount' => (float) ($paymentsByMonth->get($month->format('Y-m'))?->sum('amount') ?? 0),
                ]);
            }
        }
        $attendanceToday = $features['attendance'] ? $this->dashboard->attendanceByStatusToday() : collect();
        $stats = [
            'students' => $features['students'] ? $this->dashboard->activeStudentCount() : 0,
            'collections' => $features['fees'] ? $this->dashboard->collectedBetween($monthStart, $monthEnd) : 0,
            'pending' => $features['fee_balance'] ? $this->dashboard->outstandingBalance() : 0,
            'inquiries' => $features['inquiries'] ? $this->dashboard->openInquiryCount() : 0,
            'income' => $features['accounts'] ? $this->dashboard->accountTotal('income', $monthStart, $monthEnd) : 0,
            'expenses' => $features['accounts'] ? $this->dashboard->accountTotal('expense', $monthStart, $monthEnd) : 0,
        ];

        return compact('stats', 'payments', 'features', 'collectionHistory', 'attendanceToday');
    }
}
