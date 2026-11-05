<?php

namespace App\Services;

use App\Models\AcademyClass;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeWorkspaceService
{
    public function feeHeads(string $scope, Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), ['name', 'amount', 'active'], true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $heads = FeeHead::query()->where('scope', $scope)->with(['academyClass', 'student.academyClass'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($filter) use ($search): void {
                    $filter->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('academyClass', fn ($classQuery) => $classQuery->where('name', 'like', '%'.$search.'%')->orWhere('section', 'like', '%'.$search.'%'))
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$search.'%')->orWhere('admission_no', 'like', '%'.$search.'%'));
                });
            })->when($search === '', fn ($query) => $query->whereRaw('1 = 0'))->orderBy($sort, $direction);
        $printMode = $request->boolean('print');
        abort_if($printMode && $search === '', 422, 'Search fee heads before printing.');
        $heads = $printMode ? $heads->get() : $heads->paginate(12)->withQueryString();
        if ($printMode) {
            return view('documents.fee-heads-print', compact('scope', 'search', 'heads') + ['academy' => $request->user()->academy]);
        }
        $searchChoices = FeeHead::query()->where('scope', $scope)->with(['academyClass', 'student.academyClass'])->orderBy('name')->get()
            ->map(fn (FeeHead $head): array => [
                'value' => (string) $head->id,
                'query' => $head->name,
                'sort' => $head->name,
                'label' => collect([$head->name, $head->academyClass?->name, $head->academyClass?->section, $head->student?->name, $head->student?->admission_no])->filter()->unique()->implode(' · '),
            ]);
        $classes = AcademyClass::query()->where('active', true)->orderBy('name')->get();
        $students = Student::query()->where('status', 'active')->with('academyClass')->orderBy('name')->get();

        return view('fee-heads.index', compact('scope', 'search', 'heads', 'searchChoices', 'classes', 'students', 'sort', 'direction'));
    }

    public function bulkFeeForm(): View
    {
        $feeHeads = FeeHead::query()->where('active', true)->with(['academyClass', 'student.academyClass'])->orderBy('scope')->orderBy('name')->get();

        return view('fees.bulk', ['students' => Student::query()->where('status', 'active')->count(), 'classes' => AcademyClass::query()->where('active', true)->orderBy('name')->get(), 'feeHeads' => $feeHeads]);
    }

    public function monthlyFeeStudents(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $studentId = ctype_digit((string) $request->query('student_id')) ? (string) $request->query('student_id') : '';
        $classId = ctype_digit((string) $request->query('class_id')) ? (string) $request->query('class_id') : '';
        $hasSearch = $search !== '' || $studentId !== '' || $classId !== '';
        $sort = in_array($request->query('sort'), ['name', 'admission_no', 'monthly_fee', 'fee_adjust_month'], true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $students = Student::query()->with('academyClass')->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where(fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$search.'%')->orWhere('admission_no', 'like', '%'.$search.'%')->orWhere('father_name', 'like', '%'.$search.'%')->orWhere('guardian_phone', 'like', '%'.$search.'%')))
            ->when($studentId !== '', fn ($query) => $query->whereKey($studentId))
            ->when($classId !== '', fn ($query) => $query->where('class_id', $classId))
            ->when(! $hasSearch, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy($sort, $direction);
        $printMode = $request->boolean('print');
        abort_if($printMode && ! $hasSearch, 422, 'Search for students before printing.');
        $students = $printMode ? $students->get() : $students->paginate(20)->withQueryString();
        if ($printMode) {
            return view('documents.monthly-fees-print', compact('students', 'search') + ['academy' => $request->user()->academy]);
        }
        $classes = AcademyClass::query()->orderBy('name')->get(['id', 'name', 'section']);
        $studentOptions = Student::query()->where('status', 'active')->with('academyClass')->orderBy('name')->get(['id', 'admission_no', 'name', 'father_name', 'class_id', 'guardian_phone']);

        return view('fees.monthly-students', compact('students', 'classes', 'search', 'studentId', 'studentOptions', 'classId', 'hasSearch', 'sort', 'direction'));
    }

    public function feeDesk(Request $request): View
    {
        $user = $request->user();
        $features = [
            'students' => $user->hasFeature('students'),
            'monthly_students' => $user->hasFeature('fees.monthly_students'),
            'single_bill' => $user->hasFeature('fees.single'),
            'bulk_bills' => $user->hasFeature('fees.bulk'),
            'view_bills' => $user->hasFeature('fees.invoices'),
            'collect_payment' => $user->hasFeature('fees.collect'),
            'view_receipts' => $user->hasFeature('fees.receipts'),
            'pending_report' => $user->hasFeature('fees.reports.pending'),
            'defaulters' => $user->hasFeature('fees.defaulters'),
            'fee_heads_academy' => $user->hasFeature('fee_heads.academy'),
            'fee_heads_class' => $user->hasFeature('fee_heads.class'),
            'fee_heads_student' => $user->hasFeature('fee_heads.student'),
        ];
        abort_unless(in_array(true, $features, true), 403, 'Fee tools have not been enabled for your academy.');

        $showBalanceSummary = $features['view_bills'] || $features['pending_report'] || $features['defaulters'];
        $showCollectionSummary = $features['view_receipts'];
        $stats = [];

        if ($showBalanceSummary) {
            $balanceSummary = FeeInvoice::query()->withOpenBalanceSummary()->first();
            $stats['open_bills'] = (int) ($balanceSummary?->open_count ?? 0);
            $stats['outstanding'] = (float) ($balanceSummary?->outstanding_total ?? 0);
        }

        if ($showCollectionSummary) {
            $stats['collected_this_month'] = (float) FeePayment::query()
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');
        }

        $feeHeadScope = collect(['academy', 'class', 'student'])
            ->first(fn (string $scope): bool => $features['fee_heads_'.$scope])
            ?? null;

        return view('fees.desk', compact('features', 'stats', 'feeHeadScope'));
    }
}
