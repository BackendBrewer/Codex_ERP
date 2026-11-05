<?php

namespace App\Services;

use App\Http\Requests\FeeReportRequest;
use App\Models\AcademyClass;
use App\Models\AccountEntry;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcademyReportingService
{
    public function reports(FeeReportRequest $request): View
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $income = AccountEntry::query()->where('type', 'income')->whereBetween('entry_date', [$start, $end])->sum('amount');
        $expenses = AccountEntry::query()->where('type', 'expense')->whereBetween('entry_date', [$start, $end])->sum('amount');
        $collected = FeePayment::query()->whereBetween('paid_at', [$start, $end])->sum('amount');
        $outstanding = (float) (FeeInvoice::query()->withOpenBalanceSummary()->value('outstanding_total') ?? 0);
        $attendance = AttendanceRecord::query()->whereDate('attendance_date', today())->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $feeReport = in_array($request->query('fee_report'), ['pending', 'date', 'month', 'summary', 'recovery', 'defaulters', 'adjust'], true) ? $request->query('fee_report') : null;
        $reportDate = $request->query('date', today()->toDateString());
        $reportMonth = $request->query('month', now()->format('Y-m'));
        $reportAsOf = $request->query('as_of', today()->toDateString());
        $feeReportSearch = trim((string) $request->query('q', ''));
        $reportStudentId = ctype_digit((string) $request->query('student_id')) ? (string) $request->query('student_id') : '';
        $reportClassId = ctype_digit((string) $request->query('class_id')) ? (string) $request->query('class_id') : '';
        $hasReportSearch = $feeReportSearch !== '' || $reportStudentId !== '' || $reportClassId !== '' || $request->filled('date') || $request->filled('month') || $request->filled('as_of');
        $printMode = $request->boolean('print');
        abort_if($printMode && ! $hasReportSearch, 422, 'Search for report records before printing.');
        $feePayments = FeePayment::query()->with(['student.academyClass', 'invoice']);
        if ($feeReport === 'date' || $feeReport === 'summary') {
            $feePayments->whereDate('paid_at', $reportDate);
        } elseif ($feeReport === 'month') {
            $feePayments->whereYear('paid_at', substr($reportMonth, 0, 4))->whereMonth('paid_at', substr($reportMonth, 5, 2));
        }
        if ($feeReportSearch !== '') {
            $feePayments->where(function ($query) use ($feeReportSearch): void {
                $query->where('receipt_no', 'like', '%'.$feeReportSearch.'%')
                    ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$feeReportSearch.'%')->orWhere('admission_no', 'like', '%'.$feeReportSearch.'%')->orWhere('guardian_phone', 'like', '%'.$feeReportSearch.'%'))
                    ->orWhereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('invoice_no', 'like', '%'.$feeReportSearch.'%'));
            });
        }
        if ($reportClassId !== '') {
            $feePayments->whereHas('student', fn ($query) => $query->where('class_id', $reportClassId));
        }
        if ($reportStudentId !== '') {
            $feePayments->where('student_id', $reportStudentId);
        }
        if (! $hasReportSearch) {
            $feePayments->whereRaw('1 = 0');
        }
        $feePayments = $feePayments->latest('paid_at')->get();
        $invoiceQuery = FeeInvoice::query()->with(['student.academyClass', 'payments'])
            ->when(in_array($feeReport, ['pending', 'recovery', 'defaulters'], true), fn ($query) => $query->whereIn('status', ['unpaid', 'partial']))
            ->when($feeReport === 'recovery', fn ($query) => $query->whereDate('due_date', '<', today()))
            ->when($feeReport === 'defaulters', fn ($query) => $query->whereDate('due_date', '<=', $reportAsOf))
            ->when($feeReport === 'adjust', fn ($query) => $query->whereYear('invoice_month', substr($reportMonth, 0, 4))->whereMonth('invoice_month', substr($reportMonth, 5, 2)));
        if ($feeReportSearch !== '') {
            $invoiceQuery->where(function ($query) use ($feeReportSearch): void {
                $query->where('invoice_no', 'like', '%'.$feeReportSearch.'%')->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$feeReportSearch.'%')->orWhere('admission_no', 'like', '%'.$feeReportSearch.'%')->orWhere('guardian_phone', 'like', '%'.$feeReportSearch.'%'));
            });
        }
        if ($reportClassId !== '') {
            $invoiceQuery->whereHas('student', fn ($query) => $query->where('class_id', $reportClassId));
        }
        if ($reportStudentId !== '') {
            $invoiceQuery->where('student_id', $reportStudentId);
        }
        if (! $hasReportSearch) {
            $invoiceQuery->whereRaw('1 = 0');
        }
        $feeInvoices = $invoiceQuery->latest('invoice_month')->get();
        if ($feeReport === 'defaulters') {
            $feeInvoices = $feeInvoices->filter(fn (FeeInvoice $invoice): bool => max(0, (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee - (float) $invoice->payments->sum('amount')) > 0)->values();
        }
        $defaulterRows = $feeReport === 'defaulters'
            ? $feeInvoices->groupBy('student_id')->map(function (Collection $invoices): array {
                $firstInvoice = $invoices->first();

                return [
                    'student' => $firstInvoice->student,
                    'invoice_count' => $invoices->count(),
                    'oldest_due' => $invoices->min('due_date'),
                    'balance' => $invoices->sum(fn (FeeInvoice $invoice): float => max(0, (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee - (float) $invoice->payments->sum('amount'))),
                ];
            })->values()
            : collect();
        $reportTitle = match ($feeReport) {
            'pending' => 'Pending Balance Report', 'date' => 'Fee Collection Detail By Date', 'month' => 'Fee Collection Detail By Month',
            'summary' => 'Fee Collection Summary By Date', 'recovery' => 'Fee Recovery Report', 'defaulters' => 'Basic / Defaulters Fee List',
            'adjust' => 'Monthly Generated Fee Bills', default => null,
        };

        $classOptions = AcademyClass::query()->orderBy('name')->get(['id', 'name', 'section']);
        $studentOptions = Student::query()->with('academyClass')->orderBy('name')->get(['id', 'admission_no', 'name', 'class_id', 'guardian_phone']);

        if ($printMode) {
            return view('documents.fee-report-print', compact('feeReport', 'reportTitle', 'feePayments', 'feeInvoices', 'feeReportSearch', 'defaulterRows', 'reportAsOf') + ['academy' => $request->user()->academy]);
        }
        $reportSearchChoices = collect();
        if ($feeReport && $feeReport !== 'defaulters') {
            $reportSearchChoices = in_array($feeReport, ['date', 'month', 'summary'], true)
                ? FeePayment::query()->with(['student.academyClass', 'invoice'])->latest('paid_at')->get()->map(fn (FeePayment $payment): array => [
                    'value' => (string) $payment->id,
                    'query' => $payment->receipt_no,
                    'label' => 'Receipt · '.$payment->receipt_no.' · '.$payment->student->name.' · '.$payment->student->admission_no.' · PKR '.number_format((float) $payment->amount, 2),
                ])
                : FeeInvoice::query()->with(['student.academyClass'])->latest('invoice_month')->get()->map(fn (FeeInvoice $invoice): array => [
                    'value' => (string) $invoice->id,
                    'query' => $invoice->invoice_no,
                    'label' => 'Challan · '.$invoice->invoice_no.' · '.$invoice->student->name.' · '.$invoice->student->admission_no.' · '.$invoice->invoice_month->format('M Y'),
                ]);
        }

        return view('reports', compact('income', 'expenses', 'collected', 'outstanding', 'attendance', 'feeReport', 'reportDate', 'reportMonth', 'reportAsOf', 'feePayments', 'feeInvoices', 'reportTitle', 'feeReportSearch', 'reportStudentId', 'reportClassId', 'classOptions', 'studentOptions', 'reportSearchChoices', 'hasReportSearch', 'defaulterRows'));
    }
}
