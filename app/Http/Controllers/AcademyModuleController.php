<?php

namespace App\Http\Controllers;

use App\Models\AcademyClass;
use App\Models\AccountEntry;
use App\Models\AttendanceRecord;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Inquiry;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademyModuleController extends Controller
{
    public function index(string $module, Request $request): View
    {
        $this->authorizeModuleFeature($module, 'index');
        $definition = $this->definition($module);
        $query = ($definition['model'])::query()->with($definition['with'] ?? []);
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
        $records = $query->latest()->paginate(15)->withQueryString();
        $feeStats = null;
        $classOptions = collect();
        if ($module === 'fees') {
            $feeStats = [
                'billed' => (float) FeeInvoice::query()->where('status', '!=', 'waived')->sum(DB::raw('amount - discount + late_fee')),
                'collected' => (float) FeePayment::query()->sum('amount'),
                'outstanding' => FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->withSum('payments as paid_total', 'amount')->get()->sum(fn (FeeInvoice $invoice): float => max(0, (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee - (float) $invoice->paid_total)),
                'open_count' => FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->count(),
            ];
            $classOptions = AcademyClass::query()->orderBy('name')->get(['id', 'name', 'section']);
        }

        return view('modules.index', compact('module', 'definition', 'records', 'search', 'status', 'month', 'classId', 'paymentMethod', 'paymentMonth', 'feeStats', 'classOptions'));
    }

    public function create(string $module): View
    {
        $this->authorizeModuleFeature($module, 'create');
        $definition = $this->definition($module);
        $record = null;
        $options = $this->options($definition, null, $module === 'fees');

        return view('modules.form', compact('module', 'definition', 'record', 'options'));
    }

    public function edit(string $module, int $id): View
    {
        $this->authorizeModuleFeature($module, 'edit');
        $definition = $this->definition($module);
        $record = ($definition['model'])::query()->findOrFail($id);
        $options = $this->options($definition, $record, $module === 'fees');

        return view('modules.form', compact('module', 'definition', 'record', 'options'));
    }

    public function store(string $module, Request $request): RedirectResponse
    {
        $this->authorizeModuleFeature($module, 'store');
        $definition = $this->definition($module);
        $data = $this->validatedData($module, $definition, $request);
        $this->applyDefaults($module, $data);
        $this->validateStudentPhoto($module, $request);
        if ($module === 'fees') {
            $data['discount'] = $data['discount'] ?? 0;
            $data['late_fee'] = $data['late_fee'] ?? 0;
            $data['status'] = $data['status'] ?: 'unpaid';
            $duplicate = FeeInvoice::query()->where('student_id', $data['student_id'])->whereDate('invoice_month', $data['invoice_month'])->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['invoice_month' => 'This student already has a fee bill for the selected month.']);
            }
            $student = Student::query()->findOrFail($data['student_id']);
            $this->ensureFeeStartMonth($student, $data['invoice_month']);
            $breakdown = $this->feeBreakdown($student);
            $data['amount'] = $breakdown['total'];
            $data['fee_head_details'] = $breakdown['items'];
        }

        DB::transaction(function () use ($module, $definition, $data, $request): void {
            if ($module === 'attendance') {
                AttendanceRecord::query()->updateOrCreate(
                    ['student_id' => $data['student_id'], 'attendance_date' => $data['attendance_date']],
                    ['status' => $data['status'], 'note' => $data['note'] ?? null],
                );

                return;
            }
            if ($module === 'payments') {
                $this->recordPayment($data, $request);

                return;
            }
            if ($module === 'results') {
                $this->validateMarks($data);
                ExamResult::query()->updateOrCreate(
                    ['examination_id' => $data['examination_id'], 'student_id' => $data['student_id']],
                    ['marks_obtained' => $data['marks_obtained'], 'remarks' => $data['remarks'] ?? null],
                );

                return;
            }
            if ($module === 'messages') {
                $data['recipient_count'] = $data['recipient_count'] ?? 0;
                $data['provider'] = 'manual';
                $data['status'] = 'draft';
                $data['created_by'] = $request->user()->id;
            }
            if ($module === 'accounts') {
                $data['recorded_by'] = $request->user()->id;
            }
            if ($module === 'students' && $request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('academies/'.$request->user()->academy_id.'/students', 'public');
            }
            ($definition['model'])::query()->create($data);
        });

        if ($module === 'payments') {
            $destination = $request->user()->hasFeature('fees.receipts') ? route('modules.index', 'payments') : route('dashboard');

            return redirect($destination)->with('success', 'Fee payment recorded successfully.');
        }

        if ($module === 'fees' && ! $request->user()->hasFeature('fees.invoices')) {
            return redirect()->route('dashboard')->with('success', $definition['singular'].' saved successfully.');
        }

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' saved successfully.');
    }

    public function update(string $module, int $id, Request $request): RedirectResponse
    {
        $this->authorizeModuleFeature($module, 'edit');
        $definition = $this->definition($module);
        $record = ($definition['model'])::query()->findOrFail($id);
        $data = $this->validatedData($module, $definition, $request, $record);
        $this->validateStudentPhoto($module, $request);
        if ($module === 'fees') {
            $data['discount'] = $data['discount'] ?? 0;
            $data['late_fee'] = $data['late_fee'] ?? 0;
            $duplicate = FeeInvoice::query()->where('student_id', $data['student_id'])->whereDate('invoice_month', $data['invoice_month'])->where('id', '!=', $record->id)->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['invoice_month' => 'This student already has a fee bill for the selected month.']);
            }
            $paid = (float) $record->payments()->sum('amount');
            if ((int) $data['student_id'] !== (int) $record->student_id) {
                $student = Student::query()->findOrFail($data['student_id']);
                $this->ensureFeeStartMonth($student, $data['invoice_month']);
                $breakdown = $this->feeBreakdown($student);
                $data['amount'] = $breakdown['total'];
                $data['fee_head_details'] = $breakdown['items'];
            } else {
                $this->ensureFeeStartMonth($record->student, $data['invoice_month']);
            }
            $newDue = (float) $data['amount'] - (float) ($data['discount'] ?? 0) + (float) ($data['late_fee'] ?? 0);
            validator(['due' => $newDue], ['due' => ['numeric', 'min:'.$paid]])->validate();
            if (($data['status'] ?? null) !== 'waived') {
                $data['status'] = $paid >= $newDue ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
            }
        }
        if ($module === 'results') {
            $this->validateMarks($data);
        }
        $oldPhotoPath = null;
        $oldInvoiceId = $module === 'payments' ? $record->fee_invoice_id : null;
        if ($module === 'students' && $request->hasFile('photo')) {
            $oldPhotoPath = $record->photo_path;
            $data['photo_path'] = $request->file('photo')->store('academies/'.$request->user()->academy_id.'/students', 'public');
        }
        $record->update($data);
        if ($module === 'payments') {
            $this->syncInvoiceStatus(FeeInvoice::query()->findOrFail($oldInvoiceId));
            if ((int) $record->fee_invoice_id !== (int) $oldInvoiceId) {
                $this->syncInvoiceStatus(FeeInvoice::query()->findOrFail($record->fee_invoice_id));
            }
        }
        if ($oldPhotoPath !== null) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        if ($module === 'fees' && ! $request->user()->hasFeature('fees.invoices')) {
            return redirect()->route('dashboard')->with('success', $definition['singular'].' updated successfully.');
        }

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' updated successfully.');
    }

    public function destroy(string $module, int $id): RedirectResponse
    {
        $this->authorizeModuleFeature($module, 'edit');
        $definition = $this->definition($module);
        $record = ($definition['model'])::query()->findOrFail($id);
        abort_if($module === 'fees' && $record->payments()->exists(), 422, 'A challan with recorded receipts cannot be deleted. Edit its balance or keep the payment history.');
        $invoiceId = $module === 'payments' ? $record->fee_invoice_id : null;
        $photoPath = $module === 'students' ? $record->photo_path : null;
        $record->delete();
        if ($invoiceId !== null) {
            $this->syncInvoiceStatus(FeeInvoice::query()->findOrFail($invoiceId));
        }
        if ($photoPath !== null) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' deleted.');
    }

    public function admitInquiry(int $id): RedirectResponse
    {
        $this->requireFeature('inquiries');
        DB::transaction(function () use ($id): void {
            $inquiry = Inquiry::query()->lockForUpdate()->findOrFail($id);
            abort_if($inquiry->status === 'admitted', 422, 'This inquiry has already been admitted.');
            abort_if($inquiry->class_id === null, 422, 'Assign an interested class before admission.');
            abort_if(Student::query()->where('guardian_phone', $inquiry->phone)->where('name', $inquiry->name)->exists(), 422, 'A student with these details already exists.');

            $class = AcademyClass::query()->findOrFail($inquiry->class_id);
            Student::query()->create([
                'admission_no' => 'UA-'.now()->format('ym').'-'.Str::upper(Str::random(5)),
                'name' => $inquiry->name,
                'father_name' => $inquiry->guardian_name ?: 'Guardian',
                'guardian_phone' => $inquiry->phone,
                'class_id' => $class->id,
                'admission_date' => today()->toDateString(),
                'monthly_fee' => $class->monthly_fee,
                'fee_adjust_month' => now()->startOfMonth()->toDateString(),
                'status' => 'active',
            ]);
            $inquiry->update(['status' => 'admitted']);
        });

        return redirect()->route('modules.index', 'students')->with('success', 'Inquiry admitted. A student profile was created with the selected class fee.');
    }

    public function printReceipt(int $id): View
    {
        $this->requireFeature('fees.receipts');
        $document = FeePayment::query()->with(['student.academyClass', 'invoice'])->findOrFail($id);

        return view('documents.receipt', compact('document'));
    }

    public function printChallan(int $id): View
    {
        $this->requireFeature('fees.invoices');
        $document = FeeInvoice::query()->with(['student.academyClass', 'payments'])->findOrFail($id);

        return view('documents.challan', compact('document'));
    }

    public function reports(Request $request): View
    {
        $requestedReport = (string) $request->query('fee_report', '');
        $this->requireFeature(match ($requestedReport) {
            'pending' => 'fees.reports.pending', 'date' => 'fees.reports.collection_date', 'month' => 'fees.reports.collection_month',
            'summary' => 'fees.reports.summary_date', 'recovery' => 'fees.reports.recovery', 'defaulters' => 'fees.defaulters',
            'adjust' => 'fees.reports.adjust', default => 'reports.dashboard',
        });
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $income = AccountEntry::query()->where('type', 'income')->whereBetween('entry_date', [$start, $end])->sum('amount');
        $expenses = AccountEntry::query()->where('type', 'expense')->whereBetween('entry_date', [$start, $end])->sum('amount');
        $collected = FeePayment::query()->whereBetween('paid_at', [$start, $end])->sum('amount');
        $outstanding = FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->selectRaw('COALESCE(SUM(amount - discount + late_fee), 0) as total')->value('total') ?? 0;
        $attendance = AttendanceRecord::query()->whereDate('attendance_date', today())->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $feeReport = in_array($request->query('fee_report'), ['pending', 'date', 'month', 'summary', 'recovery', 'defaulters', 'adjust'], true) ? $request->query('fee_report') : null;
        $reportDate = $request->query('date', today()->toDateString());
        $reportMonth = $request->query('month', now()->format('Y-m'));
        $feeReportSearch = trim((string) $request->query('q', ''));
        $reportClassId = ctype_digit((string) $request->query('class_id')) ? (string) $request->query('class_id') : '';
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
        $feePayments = $feePayments->latest('paid_at')->limit(300)->get();
        $invoiceQuery = FeeInvoice::query()->with(['student.academyClass', 'payments'])
            ->when(in_array($feeReport, ['pending', 'recovery', 'defaulters'], true), fn ($query) => $query->whereIn('status', ['unpaid', 'partial']))
            ->when(in_array($feeReport, ['recovery', 'defaulters'], true), fn ($query) => $query->whereDate('due_date', '<', today()))
            ->when($feeReport === 'adjust', fn ($query) => $query->whereYear('invoice_month', substr($reportMonth, 0, 4))->whereMonth('invoice_month', substr($reportMonth, 5, 2)));
        if ($feeReportSearch !== '') {
            $invoiceQuery->where(function ($query) use ($feeReportSearch): void {
                $query->where('invoice_no', 'like', '%'.$feeReportSearch.'%')->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$feeReportSearch.'%')->orWhere('admission_no', 'like', '%'.$feeReportSearch.'%')->orWhere('guardian_phone', 'like', '%'.$feeReportSearch.'%'));
            });
        }
        if ($reportClassId !== '') {
            $invoiceQuery->whereHas('student', fn ($query) => $query->where('class_id', $reportClassId));
        }
        $feeInvoices = $invoiceQuery->latest('invoice_month')->limit(300)->get();
        $reportTitle = match ($feeReport) {
            'pending' => 'Pending Balance Report', 'date' => 'Fee Collection Detail By Date', 'month' => 'Fee Collection Detail By Month',
            'summary' => 'Fee Collection Summary By Date', 'recovery' => 'Fee Recovery Report', 'defaulters' => 'Basic / Defaulters Fee List',
            'adjust' => 'Monthly Generated Fee Bills', default => null,
        };

        $classOptions = AcademyClass::query()->orderBy('name')->get(['id', 'name', 'section']);

        return view('reports', compact('income', 'expenses', 'collected', 'outstanding', 'attendance', 'feeReport', 'reportDate', 'reportMonth', 'feePayments', 'feeInvoices', 'reportTitle', 'feeReportSearch', 'reportClassId', 'classOptions'));
    }

    public function feeHeads(string $scope, Request $request): View
    {
        $this->requireFeature('fee_heads.'.$scope);
        $search = trim((string) $request->query('q', ''));
        $heads = FeeHead::query()->where('scope', $scope)->with(['academyClass', 'student.academyClass'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($filter) use ($search): void {
                    $filter->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('academyClass', fn ($classQuery) => $classQuery->where('name', 'like', '%'.$search.'%')->orWhere('section', 'like', '%'.$search.'%'))
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$search.'%')->orWhere('admission_no', 'like', '%'.$search.'%'));
                });
            })->orderBy('name')->paginate(12)->withQueryString();
        $classes = AcademyClass::query()->where('active', true)->orderBy('name')->get();
        $students = Student::query()->where('status', 'active')->with('academyClass')->orderBy('name')->get();

        return view('fee-heads.index', compact('scope', 'search', 'heads', 'classes', 'students'));
    }

    public function storeFeeHead(string $scope, Request $request): RedirectResponse
    {
        $this->requireFeature('fee_heads.'.$scope);
        $data = $this->validateFeeHead($scope, $request);
        FeeHead::query()->create($data);

        return redirect()->route('fee-heads.index', $scope)->with('success', 'Fee head added. It will be included in future bills for its selected scope.');
    }

    public function updateFeeHead(string $scope, int $id, Request $request): RedirectResponse
    {
        $this->requireFeature('fee_heads.'.$scope);
        $head = FeeHead::query()->where('scope', $scope)->findOrFail($id);
        $head->update($this->validateFeeHead($scope, $request));

        return redirect()->route('fee-heads.index', $scope)->with('success', 'Fee head updated. Existing challans keep their saved fee breakdown.');
    }

    public function deleteFeeHead(string $scope, int $id): RedirectResponse
    {
        $this->requireFeature('fee_heads.'.$scope);
        FeeHead::query()->where('scope', $scope)->findOrFail($id)->delete();

        return redirect()->route('fee-heads.index', $scope)->with('success', 'Fee head removed. Previous challans are unchanged.');
    }

    public function bulkFeeForm(): View
    {
        $this->requireFeature('fees.bulk');

        return view('fees.bulk', ['students' => Student::query()->where('status', 'active')->count(), 'classes' => AcademyClass::query()->where('active', true)->orderBy('name')->get()]);
    }

    public function monthlyFeeStudents(Request $request): View
    {
        $this->requireFeature('fees.monthly_students');
        $search = trim((string) $request->query('q', ''));
        $classId = ctype_digit((string) $request->query('class_id')) ? (string) $request->query('class_id') : '';
        $students = Student::query()->with('academyClass')->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where(fn ($studentQuery) => $studentQuery->where('name', 'like', '%'.$search.'%')->orWhere('admission_no', 'like', '%'.$search.'%')->orWhere('father_name', 'like', '%'.$search.'%')->orWhere('guardian_phone', 'like', '%'.$search.'%')))
            ->when($classId !== '', fn ($query) => $query->where('class_id', $classId))
            ->orderBy('name')->paginate(20)->withQueryString();
        $classes = AcademyClass::query()->orderBy('name')->get(['id', 'name', 'section']);

        return view('fees.monthly-students', compact('students', 'classes', 'search', 'classId'));
    }

    public function generateBulkFees(Request $request): RedirectResponse
    {
        $this->requireFeature('fees.bulk');
        $data = $request->validate([
            'invoice_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'class_id' => ['nullable', 'integer', Rule::exists('academy_classes', 'id')->where('academy_id', $request->user()->academy_id)],
        ]);
        $month = $data['invoice_month'].'-01';
        $students = Student::query()->where('status', 'active')
            ->when(! empty($data['class_id']), fn ($query) => $query->where('class_id', $data['class_id']))
            ->where(function ($query) use ($month): void {
                $query->whereNull('fee_adjust_month')->orWhere('fee_adjust_month', '<=', $month);
            })->get();
        $created = 0;
        $activeHeads = FeeHead::query()->where('active', true)->get();

        DB::transaction(function () use ($students, $month, $data, $activeHeads, &$created): void {
            foreach ($students as $student) {
                $exists = FeeInvoice::query()->where('student_id', $student->id)->whereDate('invoice_month', $month)->exists();
                if ($exists) {
                    continue;
                }
                $breakdown = $this->feeBreakdown($student, $activeHeads);
                FeeInvoice::query()->create([
                    'invoice_no' => 'FB-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
                    'student_id' => $student->id,
                    'invoice_month' => $month,
                    'due_date' => $data['due_date'],
                    'amount' => $breakdown['total'],
                    'fee_head_details' => $breakdown['items'],
                    'status' => 'unpaid',
                ]);
                $created++;
            }
        });

        $destination = $request->user()->hasFeature('fees.invoices') ? route('modules.index', 'fees') : route('dashboard');

        return redirect($destination)->with('success', $created.' fee bill(s) generated. Existing bills for that month were skipped.');
    }

    /** @return array<string, mixed> */
    private function definition(string $module): array
    {
        $modules = config('academy.modules');
        abort_unless(isset($modules[$module]), 404);

        return $modules[$module];
    }

    private function authorizeModuleFeature(string $module, string $action): void
    {
        $feature = config('academy_access.module_features.'.$module);
        if ($module === 'fees') {
            $feature = in_array($action, ['create', 'store'], true) ? 'fees.single' : (in_array($action, ['edit'], true) ? 'fees.modify' : 'fees.invoices');
        } elseif ($module === 'payments') {
            $feature = in_array($action, ['create', 'store'], true) ? 'fees.collect' : 'fees.receipts';
        }
        if ($module === 'fees' && $action === 'edit') {
            abort_unless(auth()->user()?->hasFeature('fees.modify') || auth()->user()?->hasFeature('fees.reports.adjust'), 403, 'Fee bill editing has not been enabled for your academy.');

            return;
        }
        $this->requireFeature((string) $feature);
    }

    private function requireFeature(string $feature): void
    {
        abort_unless(auth()->user()?->hasFeature($feature), 403, 'This module option has not been enabled for your academy.');
    }

    /** @return array<string, mixed> */
    private function validateFeeHead(string $scope, Request $request): array
    {
        $academyId = $request->user()->academy_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'class_id' => [$scope === 'class' ? 'required' : 'nullable', 'integer', Rule::exists('academy_classes', 'id')->where('academy_id', $academyId)],
            'student_id' => [$scope === 'student' ? 'required' : 'nullable', 'integer', Rule::exists('students', 'id')->where('academy_id', $academyId)],
            'active' => ['nullable', 'boolean'],
        ]);
        $data['scope'] = $scope;
        $data['class_id'] = $scope === 'class' ? $data['class_id'] : null;
        $data['student_id'] = $scope === 'student' ? $data['student_id'] : null;
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }

    /** @return array{total: float, items: list<array{label: string, amount: float, fee_head_id: int|null}>} */
    private function feeBreakdown(Student $student, ?Collection $activeHeads = null): array
    {
        $heads = ($activeHeads ?? FeeHead::query()->where('active', true)->get())->filter(function (FeeHead $head) use ($student): bool {
            return $head->scope === 'academy'
                || ($head->scope === 'class' && (int) $head->class_id === (int) $student->class_id)
                || ($head->scope === 'student' && (int) $head->student_id === (int) $student->id);
        });
        $items = [['label' => 'Monthly tuition', 'amount' => (float) $student->monthly_fee, 'fee_head_id' => null]];
        foreach ($heads as $head) {
            $items[] = ['label' => $head->name, 'amount' => (float) $head->amount, 'fee_head_id' => $head->id];
        }

        return ['total' => array_sum(array_column($items, 'amount')), 'items' => $items];
    }

    private function ensureFeeStartMonth(Student $student, string $invoiceMonth): void
    {
        if ($student->fee_adjust_month !== null && $invoiceMonth < $student->fee_adjust_month->format('Y-m-d')) {
            throw ValidationException::withMessages(['invoice_month' => 'A fee bill cannot be generated before this student’s fee start month.']);
        }
    }

    /** @param array<string, mixed> $definition
     * @return array<string, Collection<int, array{value: int, label: string}>>
     */
    private function options(array $definition, ?Model $record = null, bool $includeFeeHeads = false): array
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
                $breakdown = $includeFeeHeads ? $this->feeBreakdown($row, $activeFeeHeads) : null;

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

    /** @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function validatedData(string $module, array $definition, Request $request, ?Model $record = null): array
    {
        $rules = [];
        foreach ($definition['fields'] as $name => $field) {
            $autoGenerated = $record === null && in_array($name, ['admission_no', 'invoice_no', 'receipt_no', 'employee_no', 'entry_no'], true);
            $rules[$name] = [(($field['required'] ?? false) && ! $autoGenerated) ? 'required' : 'nullable'];
            if (isset($field['options'])) {
                $rules[$name][] = Rule::in(array_keys($field['options']));
            } elseif (isset($field['source'])) {
                $rules[$name][] = 'integer';
                $exists = Rule::exists($this->sourceTable($field['source']), 'id');
                if ($request->user()?->role === 'admin') {
                    $exists->where('academy_id', $request->user()->academy_id);
                }
                $rules[$name][] = $exists;
            } elseif ($field['type'] === 'number') {
                $rules[$name][] = 'numeric';
                $rules[$name][] = 'min:0';
            } elseif ($field['type'] === 'date') {
                $rules[$name][] = 'date';
            } elseif ($field['type'] === 'month') {
                $rules[$name][] = 'date_format:Y-m';
            } elseif ($field['type'] === 'datetime-local') {
                $rules[$name][] = 'date';
            } else {
                $rules[$name][] = 'string';
                $maxLength = $module === 'messages' && $name === 'message' ? 160 : ($field['type'] === 'textarea' ? 2000 : 255);
                $rules[$name][] = 'max:'.$maxLength;
            }
        }
        if ($module === 'classes') {
            $rules['active'] = ['nullable', 'boolean'];
        }
        $data = $request->validate($rules);
        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'month' && ! empty($data[$name])) {
                $data[$name] .= '-01';
            }
        }
        if ($module === 'payments') {
            $invoice = FeeInvoice::query()->with('payments')->findOrFail($data['fee_invoice_id']);
            abort_unless($invoice->student_id === (int) $data['student_id'], 422, 'The student selected does not match this challan.');
            $due = (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee;
            $alreadyPaid = (float) $invoice->payments->sum('amount');
            if ($record instanceof FeePayment && (int) $record->fee_invoice_id === (int) $invoice->id) {
                $alreadyPaid -= (float) $record->amount;
            }
            $balance = max(0, $due - $alreadyPaid);
            validator($data, ['amount' => ['numeric', 'min:0.01', 'max:'.$balance]])->validate();
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function applyDefaults(string $module, array &$data): void
    {
        if ($module === 'students') {
            $data['admission_fee'] = $data['admission_fee'] ?? 0;
        }
        if ($module === 'students' && empty($data['admission_no'])) {
            $data['admission_no'] = 'UA-'.now()->format('ym').'-'.Str::upper(Str::random(5));
        }
        if ($module === 'fees' && empty($data['invoice_no'])) {
            $data['invoice_no'] = 'CH-'.now()->format('ym').'-'.Str::upper(Str::random(5));
        }
        if ($module === 'payments' && empty($data['receipt_no'])) {
            $data['receipt_no'] = 'RC-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        }
        if ($module === 'staff' && empty($data['employee_no'])) {
            $data['employee_no'] = 'EMP-'.Str::upper(Str::random(6));
        }
        if ($module === 'accounts' && empty($data['entry_no'])) {
            $data['entry_no'] = 'GL-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        }
    }

    /** @param array<string, mixed> $data */
    private function validateMarks(array $data): void
    {
        $exam = Examination::query()->findOrFail($data['examination_id']);
        $student = Student::query()->findOrFail($data['student_id']);
        abort_unless($student->class_id === $exam->class_id, 422, 'The selected student is not enrolled in this exam class.');
        validator($data, ['marks_obtained' => ['numeric', 'min:0', 'max:'.$exam->total_marks]])->validate();
    }

    private function sourceTable(string $source): string
    {
        return match ($source) {
            'classes' => 'academy_classes', 'students' => 'students', 'invoices' => 'fee_invoices', 'exams' => 'examinations', default => 'id',
        };
    }

    private function validateStudentPhoto(string $module, Request $request): void
    {
        if ($module === 'students') {
            $request->validate(['photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        }
    }

    /** @param array<string, mixed> $data */
    private function recordPayment(array $data, Request $request): void
    {
        $invoice = FeeInvoice::query()->findOrFail($data['fee_invoice_id']);
        $data['collected_by'] = $request->user()->id;
        FeePayment::query()->create($data);
        $due = (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee;
        $paid = (float) $invoice->payments()->sum('amount');
        $invoice->update(['status' => $paid >= $due ? 'paid' : 'partial']);
    }

    private function syncInvoiceStatus(FeeInvoice $invoice): void
    {
        $due = (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee;
        $paid = (float) $invoice->payments()->sum('amount');
        $invoice->update(['status' => $paid >= $due ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')]);
    }
}
