<?php

namespace App\Http\Controllers;

use App\Models\AccountEntry;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Inquiry;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $features = [
            'students' => $user->hasFeature('students'), 'fees' => $user->hasFeature('fees.receipts'),
            'fee_balance' => $user->hasFeature('fees.invoices') || $user->hasFeature('fees.reports.pending'),
            'inquiries' => $user->hasFeature('inquiries'), 'accounts' => $user->hasFeature('accounts'),
            'reports' => $user->hasFeature('reports.dashboard'), 'attendance' => $user->hasFeature('attendance'),
        ];
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $payments = $features['fees'] ? FeePayment::query()->with(['student.academyClass'])->latest('paid_at')->limit(8)->get() : collect();
        $collectionHistory = collect();
        if ($features['fees']) {
            $historyStart = Carbon::now()->startOfMonth()->subMonths(5);
            $paymentsByMonth = FeePayment::query()->whereBetween('paid_at', [$historyStart, $monthEnd])->get(['amount', 'paid_at'])->groupBy(fn (FeePayment $payment): string => $payment->paid_at->format('Y-m'));
            for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
                $month = Carbon::now()->startOfMonth()->subMonths($monthOffset);
                $collectionHistory->push([
                    'label' => $month->format('M'),
                    'amount' => (float) ($paymentsByMonth->get($month->format('Y-m'))?->sum('amount') ?? 0),
                ]);
            }
        }
        $attendanceToday = $features['attendance']
            ? AttendanceRecord::query()->whereDate('attendance_date', Carbon::today())->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();
        $stats = [
            'students' => $features['students'] ? Student::query()->where('status', 'active')->count() : 0,
            'collections' => $features['fees'] ? FeePayment::query()->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount') : 0,
            'pending' => $features['fee_balance'] ? FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->withSum('payments as paid_total', 'amount')->get()->sum(fn (FeeInvoice $invoice): float => max(0, (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee - (float) $invoice->paid_total)) : 0,
            'inquiries' => $features['inquiries'] ? Inquiry::query()->whereIn('status', ['new', 'contacted', 'visit'])->count() : 0,
            'income' => $features['accounts'] ? AccountEntry::query()->where('type', 'income')->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('amount') : 0,
            'expenses' => $features['accounts'] ? AccountEntry::query()->where('type', 'expense')->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('amount') : 0,
        ];

        return view('dashboard', compact('stats', 'payments', 'features', 'collectionHistory', 'attendanceToday'));
    }
}
