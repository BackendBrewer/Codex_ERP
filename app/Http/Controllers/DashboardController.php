<?php

namespace App\Http\Controllers;

use App\Models\AccountEntry;
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
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $payments = FeePayment::query()->with(['student.academyClass'])->latest('paid_at')->limit(8)->get();
        $stats = [
            'students' => Student::query()->where('status', 'active')->count(),
            'collections' => FeePayment::query()->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount'),
            'pending' => FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->selectRaw('COALESCE(SUM(amount - discount + late_fee), 0) as total')->value('total') ?? 0,
            'inquiries' => Inquiry::query()->whereIn('status', ['new', 'contacted', 'visit'])->count(),
            'income' => AccountEntry::query()->where('type', 'income')->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('amount'),
            'expenses' => AccountEntry::query()->where('type', 'expense')->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('amount'),
        ];

        return view('dashboard', compact('stats', 'payments'));
    }
}
