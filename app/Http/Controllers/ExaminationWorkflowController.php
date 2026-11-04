<?php

namespace App\Http\Controllers;

use App\Models\AcademyClass;
use App\Models\AttendanceRecord;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\MessageLog;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExaminationWorkflowController extends Controller
{
    public function index(): View
    {
        $this->requireFeature('exams');
        $classes = AcademyClass::query()->where('active', true)->orderBy('name')->orderBy('section')->get();
        $examinations = Examination::query()->with('academyClass')->latest('exam_date')->latest()->limit(30)->get();

        return view('examination.index', compact('classes', 'examinations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireFeature('exams');
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:academy_classes,id'],
            'subject' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:120'],
            'month' => ['required', 'date_format:Y-m'],
            'exam_date' => ['nullable', 'date'],
            'total_marks' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'passing_marks' => ['required', 'numeric', 'min:0', 'lte:total_marks'],
        ]);
        abort_unless(AcademyClass::query()->whereKey($data['class_id'])->exists(), 404);
        $date = $data['exam_date'] ?? $data['month'].'-01';
        abort_unless(substr($date, 0, 7) === $data['month'], 422, 'The exam date must be in the selected month.');

        $examination = Examination::query()->create([
            'class_id' => $data['class_id'],
            'subject' => $data['subject'],
            'title' => $data['title'],
            'exam_date' => $date,
            'total_marks' => $data['total_marks'],
            'passing_marks' => $data['passing_marks'],
        ]);

        return redirect()->route('examination.award-lists.show', $examination)->with('success', 'Award list created. Enter marks and attendance on the printable sheet.');
    }

    public function awardList(Examination $examination): View
    {
        $this->requireFeature('exams');
        $examination->load('academyClass');
        $students = Student::query()->where('class_id', $examination->class_id)->where('status', 'active')->with([
            'examResults' => fn ($query) => $query->where('examination_id', $examination->id),
            'attendanceRecords' => fn ($query) => $query->whereDate('attendance_date', $examination->exam_date),
        ])->orderBy('name')->get();

        return view('examination.award-list', compact('examination', 'students'));
    }

    public function updateMarks(Examination $examination, Request $request): RedirectResponse
    {
        $this->requireFeature('exams');
        $data = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*' => ['required', 'numeric', 'min:-1', 'max:'.$examination->total_marks],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:present,absent,leave'],
        ]);
        abort_unless(array_keys($data['marks']) === array_keys($data['attendance']), 422, 'Marks and attendance must include the same students.');
        $eligibleIds = Student::query()->where('class_id', $examination->class_id)->where('status', 'active')->whereIn('id', array_keys($data['marks']))->pluck('id')->map(fn ($id): string => (string) $id)->all();
        if (count($eligibleIds) !== count($data['marks'])) {
            throw ValidationException::withMessages(['marks' => 'One or more students are not enrolled in this class. Refresh the award list and try again.']);
        }

        DB::transaction(function () use ($data, $eligibleIds, $examination): void {
            foreach ($eligibleIds as $studentId) {
                $attendance = $data['attendance'][$studentId];
                AttendanceRecord::query()->updateOrCreate(
                    ['student_id' => $studentId, 'attendance_date' => $examination->exam_date->toDateString()],
                    ['status' => $attendance, 'note' => 'Exam: '.$examination->title.' · '.$examination->subject],
                );
                ExamResult::query()->updateOrCreate(
                    ['examination_id' => $examination->id, 'student_id' => $studentId],
                    ['marks_obtained' => $attendance === 'absent' ? -1 : $data['marks'][$studentId]],
                );
            }
        });

        return redirect()->route('examination.award-lists.show', $examination)->with('success', 'Marks saved. Use -1 for an absent student.');
    }

    public function printAwardList(Examination $examination, Request $request): View
    {
        $this->requireFeature('exams');
        $examination->load('academyClass');
        $students = Student::query()->where('class_id', $examination->class_id)->where('status', 'active')->orderBy('name')->get();
        $academy = $request->user()->academy;

        return view('documents.award-list', compact('examination', 'students', 'academy'));
    }

    public function resultSheet(Request $request): View
    {
        $this->requireFeature('results');
        $classId = $request->integer('class_id') ?: null;
        $month = (string) $request->query('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/D', $month) === 1, 422, 'Select a valid month.');
        $classes = AcademyClass::query()->where('active', true)->orderBy('name')->orderBy('section')->get();
        $students = collect();
        $examinations = collect();
        $academy = $request->user()->academy;
        if ($classId !== null) {
            abort_unless(AcademyClass::query()->whereKey($classId)->exists(), 404);
            $examinations = Examination::query()->where('class_id', $classId)->whereYear('exam_date', substr($month, 0, 4))->whereMonth('exam_date', substr($month, 5, 2))->with('results')->orderBy('subject')->orderBy('title')->get();
            $students = Student::query()->where('class_id', $classId)->where('status', 'active')->with('examResults')->orderBy('name')->get();
            $students->each(function (Student $student) use ($examinations): void {
                $student->setAttribute('selected_results', $examinations->mapWithKeys(fn (Examination $exam) => [$exam->id => $exam->results->firstWhere('student_id', $student->id)]));
            });
        }

        if ($request->boolean('print')) {
            abort_unless($classId !== null && $examinations->isNotEmpty(), 422, 'Choose a class and month with processed results before printing.');

            return view('documents.result-sheet', compact('classes', 'classId', 'month', 'students', 'examinations', 'academy'));
        }

        return view('examination.result-sheet', compact('classes', 'classId', 'month', 'students', 'examinations', 'academy'));
    }

    public function resultCard(Student $student, Request $request): View
    {
        $this->requireFeature('results');
        $month = (string) $request->query('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/D', $month) === 1, 422, 'Select a valid month.');
        $student->load('academyClass');
        $examinations = Examination::query()->where('class_id', $student->class_id)->whereYear('exam_date', substr($month, 0, 4))->whereMonth('exam_date', substr($month, 5, 2))->with('results')->orderBy('subject')->orderBy('title')->get();
        $academy = $request->user()->academy;

        return view('documents.result-card', compact('student', 'examinations', 'month', 'academy'));
    }

    public function sendResultSms(Request $request): RedirectResponse
    {
        $this->requireFeature('results');
        abort_unless(Schema::hasTable('message_logs'), 503, 'The messaging log is not available.');
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:academy_classes,id'],
            'month' => ['required', 'date_format:Y-m'],
            'message' => ['required', 'string', 'max:500'],
        ]);
        abort_unless(AcademyClass::query()->whereKey($data['class_id'])->exists(), 404);
        $studentIds = Student::query()->where('class_id', $data['class_id'])->where('status', 'active')->pluck('id');
        $hasResults = ExamResult::query()->whereIn('student_id', $studentIds)->whereHas('examination', fn ($query) => $query->whereYear('exam_date', substr($data['month'], 0, 4))->whereMonth('exam_date', substr($data['month'], 5, 2)))->exists();
        abort_unless($hasResults, 422, 'There are no processed results for this class and month.');
        $recipients = Student::query()->where('class_id', $data['class_id'])->where('status', 'active')->whereNotNull('guardian_phone')->count();
        MessageLog::query()->create([
            'audience' => 'class', 'message' => $data['message'], 'recipient_count' => $recipients,
            'provider' => 'manual', 'status' => 'draft', 'created_by' => $request->user()->id,
        ]);

        return redirect()->route('examination.result-sheet', ['class_id' => $data['class_id'], 'month' => $data['month']])->with('success', 'Result SMS draft saved for '.$recipients.' parents. Connect an SMS provider to deliver it.');
    }

    private function requireFeature(string $feature): void
    {
        abort_unless(auth()->user()?->hasFeature($feature), 403, 'This module option has not been enabled for your academy.');
    }
}
