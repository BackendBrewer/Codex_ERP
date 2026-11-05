<?php

namespace App\Services;

use App\Models\AcademyClass;
use App\Models\AttendanceRecord;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Inquiry;
use App\Models\Student;
use App\Models\User;
use App\Repositories\AcademyModuleRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademyModuleRecordService
{
    public function __construct(private readonly AcademyModuleRepository $records) {}

    /** @return array<string, mixed> */
    public function definition(string $module): array
    {
        $definitions = config('academy.modules');
        abort_unless(isset($definitions[$module]), 404);

        return $definitions[$module];
    }

    public function find(string $module, int $id): Model
    {
        return $this->records->find($this->definition($module)['model'], $id);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function prepare(string $module, array $data): array
    {
        foreach ($this->definition($module)['fields'] as $name => $field) {
            if ($field['type'] === 'month' && ! empty($data[$name])) {
                $data[$name] .= '-01';
            }
        }
        if ($module === 'students') {
            $data['admission_fee'] = $data['admission_fee'] ?? 0;
            if (empty($data['admission_no'])) {
                $data['admission_no'] = 'UA-'.now()->format('ym').'-'.Str::upper(Str::random(5));
            }
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
        if ($module === 'payments' && isset($data['fee_invoice_id'])) {
            $invoice = FeeInvoice::query()->findOrFail($data['fee_invoice_id']);
            $data['student_id'] = $invoice->student_id;
        }

        return $data;
    }

    public function assertFeeBillCanBeDeleted(Model $record): void
    {
        abort_if($record instanceof FeeInvoice && $record->payments()->exists(), 422, 'A bill with recorded payments cannot be deleted. Edit its balance or keep the payment history.');
    }

    public function validateMarks(array $data): void
    {
        $exam = Examination::query()->findOrFail($data['examination_id']);
        $student = Student::query()->findOrFail($data['student_id']);
        abort_unless((int) $student->class_id === (int) $exam->class_id, 422, 'The selected student is not enrolled in this exam class.');
        validator($data, ['marks_obtained' => ['numeric', 'min:0', 'max:'.$exam->total_marks]])->validate();
    }

    /** @param array<string, mixed> $data */
    public function create(string $module, array $data): Model
    {
        return $this->records->create($this->definition($module)['model'], $data);
    }

    /** @param array<string, mixed> $data */
    public function store(string $module, array $data, User $user, ?UploadedFile $studentPhoto = null): ?FeePayment
    {
        if ($module === 'fees') {
            $data['discount'] = $data['discount'] ?? 0;
            $data['late_fee'] = $data['late_fee'] ?? 0;
            $data['status'] = 'unpaid';
            if (FeeInvoice::query()->where('student_id', $data['student_id'])->whereDate('invoice_month', $data['invoice_month'])->exists()) {
                throw ValidationException::withMessages(['invoice_month' => 'This student already has a fee bill for the selected month.']);
            }
            $student = Student::query()->findOrFail($data['student_id']);
            $this->ensureFeeStartMonth($student, $data['invoice_month']);
            $breakdown = $this->feeBreakdown($student);
            $data['amount'] = $breakdown['total'];
            $data['fee_head_details'] = $breakdown['items'];
        }

        $createdPayment = null;
        DB::transaction(function () use ($module, $data, $user, $studentPhoto, &$createdPayment): void {
            if ($module === 'attendance') {
                AttendanceRecord::query()->updateOrCreate(
                    ['student_id' => $data['student_id'], 'attendance_date' => $data['attendance_date']],
                    ['status' => $data['status'], 'note' => $data['note'] ?? null],
                );
            } elseif ($module === 'payments') {
                $createdPayment = $this->recordPayment($data, $user);
            } elseif ($module === 'results') {
                $this->validateMarks($data);
                ExamResult::query()->updateOrCreate(
                    ['examination_id' => $data['examination_id'], 'student_id' => $data['student_id']],
                    ['marks_obtained' => $data['marks_obtained'], 'remarks' => $data['remarks'] ?? null],
                );
            } else {
                if ($module === 'messages') {
                    $data['recipient_count'] = $data['recipient_count'] ?? 0;
                    $data['provider'] = 'manual';
                    $data['status'] = 'draft';
                    $data['created_by'] = $user->id;
                }
                if ($module === 'accounts') {
                    $data['recorded_by'] = $user->id;
                }
                if ($module === 'students' && $studentPhoto !== null) {
                    $data['photo_path'] = $studentPhoto->store('academies/'.$user->academy_id.'/students', 'public');
                }
                $this->create($module, $data);
            }
        });

        return $createdPayment;
    }

    /** @param array<string, mixed> $data */
    public function saveChanges(string $module, Model $record, array $data, User $user, ?UploadedFile $studentPhoto = null): void
    {
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
            $newDue = (float) $data['amount'] - (float) $data['discount'] + (float) $data['late_fee'];
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
        if ($module === 'students' && $studentPhoto !== null) {
            $oldPhotoPath = $record->photo_path;
            $data['photo_path'] = $studentPhoto->store('academies/'.$user->academy_id.'/students', 'public');
        }
        DB::transaction(function () use ($record, $data, $module, $oldInvoiceId): void {
            $this->update($record, $data);
            if ($module === 'payments') {
                $this->syncInvoiceStatus(FeeInvoice::query()->findOrFail($oldInvoiceId));
                if ((int) $record->fee_invoice_id !== (int) $oldInvoiceId) {
                    $this->syncInvoiceStatus(FeeInvoice::query()->findOrFail($record->fee_invoice_id));
                }
            }
        });
        if ($oldPhotoPath !== null) {
            Storage::disk('public')->delete($oldPhotoPath);
        }
    }

    public function remove(string $module, Model $record): void
    {
        $this->assertFeeBillCanBeDeleted($record);
        $invoiceId = $module === 'payments' ? $record->fee_invoice_id : null;
        $photoPath = $module === 'students' ? $record->photo_path : null;
        $this->delete($record);
        if ($invoiceId !== null) {
            $this->syncInvoiceStatus(FeeInvoice::query()->findOrFail($invoiceId));
        }
        if ($photoPath !== null) {
            Storage::disk('public')->delete($photoPath);
        }
    }

    public function admitInquiry(int $inquiryId): void
    {
        DB::transaction(function () use ($inquiryId): void {
            $inquiry = Inquiry::query()->lockForUpdate()->findOrFail($inquiryId);
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
    }

    /** @param array{operation: string, adjustment: numeric-string|int|float, reason?: string|null} $data */
    public function adjustFeeBill(int $invoiceId, array $data): int
    {
        $invoice = FeeInvoice::query()->with('payments')->findOrFail($invoiceId);
        abort_if($invoice->status === 'waived', 422, 'A waived bill cannot be adjusted.');
        $adjustment = round((float) $data['adjustment'], 2);
        $signedAdjustment = $data['operation'] === 'add' ? $adjustment : -$adjustment;
        $newAmount = round((float) $invoice->amount + $signedAdjustment, 2);
        abort_if($newAmount < 0, 422, 'The reduction cannot be greater than the bill amount.');
        validator(['due' => $newAmount - (float) $invoice->discount + (float) $invoice->late_fee], ['due' => ['numeric', 'min:'.(float) $invoice->payments->sum('amount')]])->validate();
        $details = $invoice->fee_head_details;
        if (! is_array($details) || $details === []) {
            $details = [['label' => 'Monthly tuition', 'amount' => (float) $invoice->amount, 'fee_head_id' => null]];
        }
        $details[] = ['label' => 'Monthly adjustment '.($signedAdjustment > 0 ? '(+)' : '(−)'), 'amount' => $signedAdjustment, 'fee_head_id' => null];
        $invoice->amount = $newAmount;
        $invoice->fee_head_details = $details;
        if (trim((string) ($data['reason'] ?? '')) !== '') {
            $invoice->remarks = trim((string) $invoice->remarks."\nAdjustment: ".trim((string) $data['reason']));
        }
        $invoice->save();
        $this->syncInvoiceStatus($invoice);

        return $invoice->student_id;
    }

    /** @param array{invoice_month: string, due_date: string, class_id?: int|null} $data
     * @return array{created: int, month: string}
     */
    public function generateBulkFees(array $data): array
    {
        $month = $data['invoice_month'].'-01';
        $students = Student::query()->where('status', 'active')
            ->when(! empty($data['class_id']), fn ($query) => $query->where('class_id', $data['class_id']))
            ->where(function ($query) use ($month): void {
                $query->whereNull('fee_adjust_month')->orWhere('fee_adjust_month', '<=', $month);
            })->get();
        $existingStudentIds = FeeInvoice::query()->whereDate('invoice_month', $month)->whereIn('student_id', $students->modelKeys())->pluck('student_id')->map(fn ($id): int => (int) $id)->all();
        $activeHeads = FeeHead::query()->where('active', true)->get();
        $created = 0;
        DB::transaction(function () use ($students, $existingStudentIds, $month, $data, $activeHeads, &$created): void {
            foreach ($students as $student) {
                if (in_array((int) $student->id, $existingStudentIds, true)) {
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

        return ['created' => $created, 'month' => $month];
    }

    /** @param array<string, mixed> $data */
    public function update(Model $record, array $data): void
    {
        $this->records->update($record, $data);
    }

    public function delete(Model $record): void
    {
        $this->records->delete($record);
    }

    /** @return array{total: float, items: list<array{label: string, amount: float, fee_head_id: int|null}>} */
    public function feeBreakdown(Student $student, ?Collection $activeHeads = null): array
    {
        $heads = ($activeHeads ?? FeeHead::query()->where('active', true)->get())->filter(fn (FeeHead $head): bool => $head->scope === 'academy'
            || ($head->scope === 'class' && (int) $head->class_id === (int) $student->class_id)
            || ($head->scope === 'student' && (int) $head->student_id === (int) $student->id));
        $items = [['label' => 'Monthly tuition', 'amount' => (float) $student->monthly_fee, 'fee_head_id' => null]];
        foreach ($heads as $head) {
            $items[] = ['label' => $head->name, 'amount' => (float) $head->amount, 'fee_head_id' => $head->id];
        }

        return ['total' => array_sum(array_column($items, 'amount')), 'items' => $items];
    }

    public function ensureFeeStartMonth(Student $student, string $invoiceMonth): void
    {
        if ($student->fee_adjust_month !== null && $invoiceMonth < $student->fee_adjust_month->format('Y-m-d')) {
            throw ValidationException::withMessages(['invoice_month' => 'A fee bill cannot be generated before this student’s fee start month.']);
        }
    }

    /** @param array<string, mixed> $data */
    private function recordPayment(array $data, User $user): FeePayment
    {
        $invoice = FeeInvoice::query()->findOrFail($data['fee_invoice_id']);
        $data['collected_by'] = $user->id;
        $payment = FeePayment::query()->create($data);
        $due = (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee;
        $paid = (float) $invoice->payments()->sum('amount');
        $invoice->update(['status' => $paid >= $due ? 'paid' : 'partial']);

        return $payment;
    }

    public function syncInvoiceStatus(FeeInvoice $invoice): void
    {
        if ($invoice->status === 'waived') {
            return;
        }
        $due = (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee;
        $paid = (float) $invoice->payments()->sum('amount');
        $invoice->update(['status' => $paid >= $due ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')]);
    }
}
