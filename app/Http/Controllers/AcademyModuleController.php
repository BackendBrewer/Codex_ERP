<?php

namespace App\Http\Controllers;

use App\Models\AcademyClass;
use App\Models\AccountEntry;
use App\Models\AttendanceRecord;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Inquiry;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademyModuleController extends Controller
{
    public function index(string $module, Request $request): View
    {
        $definition = $this->definition($module);
        $query = ($definition['model'])::query()->with($definition['with'] ?? []);
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($definition, $search): void {
                foreach ($definition['searchable'] as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }
        $records = $query->latest()->paginate(15)->withQueryString();

        return view('modules.index', compact('module', 'definition', 'records', 'search'));
    }

    public function create(string $module): View
    {
        $definition = $this->definition($module);
        $record = null;
        $options = $this->options($definition);

        return view('modules.form', compact('module', 'definition', 'record', 'options'));
    }

    public function edit(string $module, int $id): View
    {
        $definition = $this->definition($module);
        $record = ($definition['model'])::query()->findOrFail($id);
        $options = $this->options($definition);

        return view('modules.form', compact('module', 'definition', 'record', 'options'));
    }

    public function store(string $module, Request $request): RedirectResponse
    {
        $definition = $this->definition($module);
        $data = $this->validatedData($module, $definition, $request);
        $this->applyDefaults($module, $data);

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
            ($definition['model'])::query()->create($data);
        });

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' saved successfully.');
    }

    public function update(string $module, int $id, Request $request): RedirectResponse
    {
        $definition = $this->definition($module);
        $record = ($definition['model'])::query()->findOrFail($id);
        $data = $this->validatedData($module, $definition, $request, $record);
        if ($module === 'results') {
            $this->validateMarks($data);
        }
        $record->update($data);

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' updated successfully.');
    }

    public function destroy(string $module, int $id): RedirectResponse
    {
        $definition = $this->definition($module);
        ($definition['model'])::query()->findOrFail($id)->delete();

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' deleted.');
    }

    public function admitInquiry(int $id): RedirectResponse
    {
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
                'status' => 'active',
            ]);
            $inquiry->update(['status' => 'admitted']);
        });

        return redirect()->route('modules.index', 'students')->with('success', 'Inquiry admitted. A student profile was created with the selected class fee.');
    }

    public function printReceipt(int $id): View
    {
        $document = FeePayment::query()->with(['student.academyClass', 'invoice'])->findOrFail($id);

        return view('documents.receipt', compact('document'));
    }

    public function printChallan(int $id): View
    {
        $document = FeeInvoice::query()->with(['student.academyClass', 'payments'])->findOrFail($id);

        return view('documents.challan', compact('document'));
    }

    public function reports(): View
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $income = AccountEntry::query()->where('type', 'income')->whereBetween('entry_date', [$start, $end])->sum('amount');
        $expenses = AccountEntry::query()->where('type', 'expense')->whereBetween('entry_date', [$start, $end])->sum('amount');
        $collected = FeePayment::query()->whereBetween('paid_at', [$start, $end])->sum('amount');
        $outstanding = FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->selectRaw('COALESCE(SUM(amount - discount + late_fee), 0) as total')->value('total') ?? 0;
        $attendance = AttendanceRecord::query()->whereDate('attendance_date', today())->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('reports', compact('income', 'expenses', 'collected', 'outstanding', 'attendance'));
    }

    /** @return array<string, mixed> */
    private function definition(string $module): array
    {
        $modules = config('academy.modules');
        abort_unless(isset($modules[$module]), 404);

        return $modules[$module];
    }

    /** @param array<string, mixed> $definition
     * @return array<string, Collection<int, array{value: int, label: string}>>
     */
    private function options(array $definition): array
    {
        $sources = [];
        foreach ($definition['fields'] as $field) {
            if (isset($field['source'])) {
                $sources[$field['source']] = true;
            }
        }
        $options = [];
        if (isset($sources['classes'])) {
            $options['classes'] = AcademyClass::query()->orderBy('name')->get()->map(fn ($row): array => ['value' => $row->id, 'label' => $row->name.' — '.$row->section.' ('.$row->program.')']);
        }
        if (isset($sources['students'])) {
            $options['students'] = Student::query()->where('status', 'active')->orderBy('name')->get()->map(fn (Student $row): array => ['value' => $row->id, 'label' => $row->admission_no.' · '.$row->name.' ('.$row->guardian_phone.')']);
        }
        if (isset($sources['invoices'])) {
            $options['invoices'] = FeeInvoice::query()->whereIn('status', ['unpaid', 'partial'])->with('student')->latest()->get()->map(fn (FeeInvoice $row): array => ['value' => $row->id, 'label' => $row->invoice_no.' · '.$row->student->name.' · PKR '.number_format((float) $row->amount, 0)]);
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
            $balance = max(0, $due - (float) $invoice->payments->sum('amount'));
            validator($data, ['amount' => ['numeric', 'min:0.01', 'max:'.$balance]])->validate();
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function applyDefaults(string $module, array &$data): void
    {
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
}
