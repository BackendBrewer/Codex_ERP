<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\StudentWorkspaceRepository;
use Illuminate\View\View;

class StudentWorkspaceService
{
    public function __construct(private readonly StudentWorkspaceRepository $workspace) {}

    public function show(int $id, User $user): View
    {
        $student = $this->workspace->findStudent($id);
        $feeFeatures = collect(array_keys(config('academy_access.groups.Fees')))->filter(fn (string $feature): bool => str_starts_with($feature, 'fees.'));
        $canViewFees = $feeFeatures->contains(fn (string $feature): bool => $user->hasFeature($feature));
        $canViewPayments = $user->hasFeature('fees.receipts') || $user->hasFeature('fees.collect');
        $canViewAttendance = $user->hasFeature('attendance');
        $canViewResults = $user->hasFeature('results');
        $feeInvoices = $canViewFees ? $this->workspace->invoices($student) : collect();
        $feePayments = $canViewPayments ? $this->workspace->payments($student) : collect();
        $invoiceSummary = $canViewFees ? $this->workspace->invoiceSummary($student) : null;
        $feeSummary = [
            'billed' => (float) ($invoiceSummary?->billed_total ?? 0),
            'paid' => (float) ($invoiceSummary?->paid_total ?? 0),
            'due' => (float) ($invoiceSummary?->due_total ?? 0),
            'open_count' => (int) ($invoiceSummary?->open_count ?? 0),
        ];
        $attendanceSummary = $canViewAttendance ? $this->workspace->attendanceSummary($student) : collect();
        $attendanceRecords = $canViewAttendance ? $this->workspace->attendanceRecords($student) : collect();
        $examResults = $canViewResults ? $this->workspace->examResults($student) : collect();

        return view('students.workspace', compact('student', 'canViewFees', 'canViewPayments', 'canViewAttendance', 'canViewResults', 'feeInvoices', 'feePayments', 'feeSummary', 'attendanceSummary', 'attendanceRecords', 'examResults'));
    }
}
