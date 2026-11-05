<?php

namespace App\Services;

use App\Models\Examination;
use App\Models\Student;
use App\Models\User;
use App\Repositories\AttendanceRepository;
use App\Repositories\ExaminationRepository;
use App\Repositories\ExamResultRepository;
use App\Repositories\MessageLogRepository;
use App\Repositories\StudentRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExaminationWorkflowService
{
    public function __construct(
        private readonly ExaminationRepository $examinations,
        private readonly StudentRepository $students,
        private readonly ExamResultRepository $examResults,
        private readonly AttendanceRepository $attendance,
        private readonly MessageLogRepository $messageLogs,
    ) {}

    /** @return array{classes: Collection, examinations: Collection} */
    public function workspace(User $user): array
    {
        $this->authorizeFeature($user, 'exams');

        return ['classes' => $this->examinations->activeClasses(), 'examinations' => $this->examinations->recentAwardLists()];
    }

    /** @param array<string, mixed> $data */
    public function createAwardList(User $user, array $data): Examination
    {
        $this->authorizeFeature($user, 'exams');
        $this->examinations->findClass((int) $data['class_id']);
        abort_unless(substr((string) $data['exam_date'], 0, 7) === $data['month'], 422, 'The exam date must be in the selected month.');

        return $this->examinations->createAwardList(collect($data)->only(['class_id', 'subject', 'title', 'exam_date', 'total_marks', 'passing_marks'])->all());
    }

    /** @return array{students: Collection} */
    public function awardList(User $user, Examination $examination): array
    {
        $this->authorizeFeature($user, 'exams');

        return ['students' => $this->students->activeForAwardList($examination->class_id, $examination->id, $examination->exam_date->toDateString())];
    }

    /** @param array<string, mixed> $data */
    public function saveMarks(User $user, Examination $examination, array $data): void
    {
        $this->authorizeFeature($user, 'exams');
        if (array_keys($data['marks']) !== array_keys($data['attendance'])) {
            throw ValidationException::withMessages(['marks' => 'Marks and attendance must include the same students.']);
        }
        $eligibleIds = $this->students->activeIdsForClass($examination->class_id);
        $submittedIds = array_map('intval', array_keys($data['marks']));
        if (array_diff($submittedIds, $eligibleIds) !== [] || array_diff($eligibleIds, $submittedIds) !== []) {
            throw ValidationException::withMessages(['marks' => 'The class roster has changed. Refresh the award list and enter marks for the current students.']);
        }

        DB::transaction(function () use ($data, $examination): void {
            foreach ($data['marks'] as $studentId => $mark) {
                $status = $data['attendance'][$studentId];
                $this->attendance->saveExamAttendance($examination, (int) $studentId, $status);
                $this->examResults->saveMarks($examination, (int) $studentId, $status === 'absent' ? -1.0 : (float) $mark);
            }
        });
    }

    /** @return array{students: Collection} */
    public function printableAwardList(User $user, Examination $examination): array
    {
        $this->authorizeFeature($user, 'exams');

        return ['students' => $this->students->activeForClass($examination->class_id)];
    }

    /** @return array{classes: Collection, students: Collection, examinations: Collection} */
    public function classResults(User $user, ?int $classId, string $month): array
    {
        $this->authorizeFeature($user, 'results');
        $classes = $this->examinations->activeClasses();
        $students = collect();
        $examinations = collect();
        if ($classId !== null) {
            $this->examinations->findClass($classId);
            [$year, $monthNumber] = explode('-', $month);
            $examinations = $this->examinations->awardListsForMonth($classId, $year, $monthNumber);
            $students = $this->students->activeForClassWithResults($classId);
            $students->each(function (Student $student) use ($examinations): void {
                $student->setAttribute('selected_results', $examinations->mapWithKeys(fn (Examination $exam) => [$exam->id => $exam->results->firstWhere('student_id', $student->id)]));
            });
        }

        return compact('classes', 'students', 'examinations');
    }

    /** @return array{examinations: Collection} */
    public function studentResultCard(User $user, Student $student, string $month): array
    {
        $this->authorizeFeature($user, 'results');
        [$year, $monthNumber] = explode('-', $month);

        return ['examinations' => $this->examinations->awardListsForMonth($student->class_id, $year, $monthNumber)];
    }

    /** @param array<string, mixed> $data */
    public function createResultSmsDraft(User $user, array $data): int
    {
        $this->authorizeFeature($user, 'results');
        $classId = (int) $data['class_id'];
        $this->examinations->findClass($classId);
        [$year, $monthNumber] = explode('-', $data['month']);
        abort_unless($this->examinations->hasProcessedResultsForMonth($classId, $year, $monthNumber), 422, 'There are no processed results for this class and month.');
        $recipients = $this->students->countSmsRecipients($classId);
        $this->messageLogs->createResultDraft($data['message'], $recipients, $user->id);

        return $recipients;
    }

    private function authorizeFeature(User $user, string $feature): void
    {
        abort_unless($user->hasFeature($feature), 403, 'This module option has not been enabled for your academy.');
    }
}
