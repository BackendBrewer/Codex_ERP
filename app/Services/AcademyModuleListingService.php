<?php

namespace App\Services;

use App\Models\AcademyClass;
use App\Models\Examination;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Repositories\AcademyModuleRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AcademyModuleListingService
{
    public function __construct(
        private readonly AcademyModuleRecordService $moduleRecords,
        private readonly AcademyModuleRepository $records,
    ) {}

    public function index(string $module, Request $request): View
    {
        $definition = $this->moduleRecords->definition($module);
        $query = $this->records->listingQuery($definition['model'], $definition['with'] ?? []);
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($definition, $search): void {
                foreach ($definition['searchable'] as $column) {
                    $parts = explode('.', $column);
                    $field = array_pop($parts);
                    if ($parts === []) {
                        $builder->orWhere($field, 'like', '%'.$search.'%');
                    } else {
                        $builder->orWhereHas(implode('.', $parts), fn ($related) => $related->where($field, 'like', '%'.$search.'%'));
                    }
                }
            });
        }
        $status = '';
        $month = '';
        $classId = '';
        $paymentMethod = '';
        $paymentMonth = '';
        $studentStatus = '';
        $studentId = ctype_digit((string) $request->query('student_id')) ? (string) $request->query('student_id') : '';
        if ($module === 'fees') {
            $status = (string) $request->query('status', '');
            $month = (string) $request->query('month', '');
            $classId = (string) $request->query('class_id', '');
            if (in_array($status, ['unpaid', 'partial', 'paid', 'waived'], true)) {
                $query->where('status', $status);
            }
            if (preg_match('/^\d{4}-\d{2}$/D', $month)) {
                $query->whereYear('invoice_month', substr($month, 0, 4))->whereMonth('invoice_month', substr($month, 5, 2));
            } else {
                $month = '';
            }
            if (ctype_digit($classId) && $classId !== '') {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('class_id', (int) $classId));
            } else {
                $classId = '';
            }
        }
        if ($module === 'payments') {
            $paymentMethod = (string) $request->query('method', '');
            $paymentMonth = (string) $request->query('month', '');
            if (in_array($paymentMethod, ['cash', 'bank', 'easypaisa', 'jazzcash', 'cheque'], true)) {
                $query->where('method', $paymentMethod);
            } else {
                $paymentMethod = '';
            }
            if (preg_match('/^\d{4}-\d{2}$/D', $paymentMonth)) {
                $query->whereYear('paid_at', substr($paymentMonth, 0, 4))->whereMonth('paid_at', substr($paymentMonth, 5, 2));
            } else {
                $paymentMonth = '';
            }
        }
        if ($module === 'students') {
            $studentStatus = (string) $request->query('status', '');
            $classId = (string) $request->query('class_id', '');
            if (in_array($studentStatus, ['active', 'leave', 'struck_off', 'passed_out'], true)) {
                $query->where('status', $studentStatus);
            } else {
                $studentStatus = '';
            }
            if (ctype_digit($classId) && $classId !== '') {
                $query->where('class_id', (int) $classId);
            } else {
                $classId = '';
            }
        }
        if (in_array($module, ['fees', 'payments'], true) && $studentId !== '') {
            $query->where('student_id', $studentId);
        }
        $hasSearch = $search !== '' || (in_array($module, ['fees', 'payments'], true) && $studentId !== '') || ($module === 'students' && ($studentStatus !== '' || $classId !== '')) || ($module === 'fees' && ($status !== '' || $month !== '' || $classId !== '')) || ($module === 'payments' && ($paymentMethod !== '' || $paymentMonth !== ''));
        $sort = (string) $request->query('sort', '');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $sortableColumns = $module === 'fees'
            ? ['invoice_no', 'invoice_month', 'due_date', 'amount', 'status', 'student_name']
            : collect(array_keys($definition['columns']))->filter(fn (string $column): bool => ! str_contains($column, '.'))->all();
        if (in_array($sort, $sortableColumns, true)) {
            if ($module === 'fees' && $sort === 'student_name') {
                $query->orderBy(Student::query()->select('name')->whereColumn('students.id', 'fee_invoices.student_id'), $direction);
            } else {
                $query->orderBy($sort, $direction);
            }
        } else {
            $query->latest();
            $sort = '';
        }
        if (! $hasSearch) {
            $query->whereRaw('1 = 0');
        }
        $printMode = $request->boolean('print');
        if ($printMode) {
            abort_unless($hasSearch, 422, 'Search for records before printing.');
            $records = $query->get();
            if ($module === 'students' && $request->query('format') === 'cards') {
                return view('documents.student-cards', ['students' => $records, 'academy' => $request->user()->academy]);
            }

            return view('documents.module-print', compact('module', 'definition', 'records', 'search') + ['academy' => $request->user()->academy]);
        }
        $records = $query->paginate(15)->withQueryString();
        $feeStats = null;
        $classOptions = collect();
        $studentOptions = collect();
        if ($module === 'students') {
            $classOptions = AcademyClass::query()->orderBy('name')->get(['id', 'name', 'section']);
        } elseif ($module === 'fees') {
            $classOptions = AcademyClass::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'section']);
        }
        if ($module === 'fees') {
            $balanceSummary = FeeInvoice::query()->withOpenBalanceSummary()->first();
            $feeStats = [
                'billed' => (float) FeeInvoice::query()->where('status', '!=', 'waived')->sum(DB::raw('amount - discount + late_fee')),
                'collected' => (float) FeePayment::query()->sum('amount'),
                'outstanding' => (float) ($balanceSummary?->outstanding_total ?? 0),
                'open_count' => (int) ($balanceSummary?->open_count ?? 0),
            ];
        }
        if (in_array($module, ['fees', 'payments'], true)) {
            $studentOptions = Student::query()->with('academyClass')->orderBy('name')->limit(300)->get(['id', 'admission_no', 'name', 'class_id', 'guardian_phone']);
        }
        $searchChoices = collect();
        if (in_array($module, array_keys(config('academy.modules')), true)) {
            $preferredField = match ($module) {
                'inquiries' => 'name', 'students' => 'admission_no', 'classes' => 'name', 'fees' => 'invoice_no', 'payments' => 'receipt_no', 'attendance' => 'student.admission_no',
                'exams' => 'title', 'results' => 'student.admission_no', 'employees' => 'employee_no', 'accounts' => 'entry_no', 'messages' => 'message',
                default => $definition['searchable'][0] ?? null,
            };
            $choiceRelations = array_values(array_filter($definition['with'] ?? [], fn (string $relation): bool => $relation !== 'payments'));
            $searchChoices = ($definition['model'])::query()->with($choiceRelations)->latest()->limit(300)->get()
                ->map(function (Model $record) use ($definition, $preferredField): ?array {
                    $fields = $definition['searchable'];
                    $queryValue = $preferredField ? data_get($record, $preferredField) : null;
                    if (! is_scalar($queryValue) || trim((string) $queryValue) === '') {
                        foreach ($fields as $field) {
                            $candidate = data_get($record, $field);
                            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                                $queryValue = $candidate;
                                break;
                            }
                        }
                    }
                    if (! is_scalar($queryValue) || trim((string) $queryValue) === '') {
                        return null;
                    }
                    $label = collect($fields)->map(function (string $field) use ($record): ?string {
                        $value = data_get($record, $field);
                        if ($value instanceof \DateTimeInterface) {
                            return $value->format('d M Y');
                        }

                        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
                    })->filter()->unique()->take(4)->implode(' · ');

                    $sortValue = data_get($record, 'name') ?? data_get($record, 'student.name') ?? $queryValue;

                    return ['value' => (string) $record->getKey(), 'query' => (string) $queryValue, 'sort' => (string) $sortValue, 'label' => Str::limit($label, 160)];
                })->filter()->values();
            if (in_array($module, ['fees', 'payments'], true)) {
                $searchChoices = $searchChoices->concat($studentOptions->map(fn (Student $student): array => [
                    'value' => 'student-'.$student->id,
                    'query' => $student->admission_no,
                    'sort' => $student->name,
                    'label' => 'Student · '.$student->name.' · '.$student->admission_no.' · '.($student->academyClass?->name ?? 'No class').' · '.$student->guardian_phone,
                ]))->values();
            }
        }

        if ($module === 'students') {
            return view('students.index', compact('definition', 'records', 'search', 'classId', 'studentStatus', 'classOptions', 'searchChoices', 'hasSearch', 'sort', 'direction'));
        }

        return view('modules.index', compact('module', 'definition', 'records', 'search', 'status', 'month', 'classId', 'studentStatus', 'paymentMethod', 'paymentMonth', 'studentId', 'studentOptions', 'searchChoices', 'feeStats', 'classOptions', 'hasSearch', 'sort', 'direction'));
    }

    /** @param array<string, mixed> $definition
     * @return array<string, Collection<int, array{value: int, label: string}>>
     */
    public function options(array $definition, ?Model $record = null, bool $includeFeeHeads = false): array
    {
        $sources = [];
        foreach ($definition['fields'] as $field) {
            if (isset($field['source'])) {
                $sources[$field['source']] = true;
            }
        }
        $options = [];
        if (isset($sources['classes'])) {
            $options['classes'] = AcademyClass::query()->where('active', true)->orderBy('name')->get()->map(fn (AcademyClass $row): array => [
                'value' => $row->id,
                'label' => $row->name.' · '.$row->program.' · Section '.$row->section,
                'section_label' => $row->section,
                'name' => $row->name,
                'subject_group' => $row->program,
                'section' => $row->section,
                'monthly_fee' => $row->monthly_fee,
            ]);
        }
        if (isset($sources['students'])) {
            $activeFeeHeads = $includeFeeHeads ? FeeHead::query()->where('active', true)->get() : collect();
            $options['students'] = Student::query()->where(function ($query) use ($record): void {
                $query->where('status', 'active');
                if ($record instanceof FeePayment) {
                    $query->orWhereKey($record->student_id);
                }
            })->with('academyClass')->orderBy('name')->get()->map(function (Student $row) use ($activeFeeHeads, $includeFeeHeads): array {
                $breakdown = $includeFeeHeads ? $this->moduleRecords->feeBreakdown($row, $activeFeeHeads) : null;

                return array_merge(['value' => $row->id, 'label' => $row->admission_no.' · '.$row->name.' · '.$row->academyClass?->name.' · '.$row->guardian_phone], $includeFeeHeads ? ['fee_total' => $breakdown['total'], 'fee_heads' => $breakdown['items']] : []);
            });
        }
        if (isset($sources['invoices'])) {
            $options['invoices'] = FeeInvoice::query()->where(function ($query) use ($record): void {
                $query->whereIn('status', ['unpaid', 'partial']);
                if ($record instanceof FeePayment) {
                    $query->orWhereKey($record->fee_invoice_id);
                }
            })->with(['student', 'payments'])->latest()->get()->map(function (FeeInvoice $row) use ($record): array {
                $paid = (float) $row->payments->sum('amount');
                if ($record instanceof FeePayment && (int) $record->fee_invoice_id === (int) $row->id) {
                    $paid -= (float) $record->amount;
                }
                $balance = max(0, (float) $row->amount - (float) $row->discount + (float) $row->late_fee - $paid);

                return ['value' => $row->id, 'label' => $row->invoice_no.' · '.$row->student->name.' · '.$row->invoice_month->format('M Y').' · Due PKR '.number_format($balance, 2), 'student_id' => $row->student_id, 'balance' => $balance];
            })->filter(fn (array $invoice): bool => $invoice['balance'] > 0)->values();
        }
        if (isset($sources['exams'])) {
            $options['exams'] = Examination::query()->with('academyClass')->latest('exam_date')->get()->map(fn (Examination $row): array => ['value' => $row->id, 'label' => $row->title.' · '.$row->academyClass->name.' · '.$row->subject]);
        }

        return $options;
    }
}
