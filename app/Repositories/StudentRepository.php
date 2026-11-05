<?php

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

class StudentRepository
{
    /** @return Collection<int, Student> */
    public function activeForAwardList(int $classId, int $examinationId, string $examDate): Collection
    {
        return Student::query()->where('class_id', $classId)->where('status', 'active')->with([
            'examResults' => fn ($query) => $query->where('examination_id', $examinationId),
            'attendanceRecords' => fn ($query) => $query->whereDate('attendance_date', $examDate),
        ])->orderBy('name')->get();
    }

    /** @return Collection<int, Student> */
    public function activeForClass(int $classId): Collection
    {
        return Student::query()->where('class_id', $classId)->where('status', 'active')->orderBy('name')->get();
    }

    /** @return Collection<int, Student> */
    public function activeForClassWithResults(int $classId): Collection
    {
        return Student::query()->where('class_id', $classId)->where('status', 'active')->with('examResults')->orderBy('name')->get();
    }

    public function countSmsRecipients(int $classId): int
    {
        return Student::query()->where('class_id', $classId)->where('status', 'active')->whereNotNull('guardian_phone')->count();
    }

    /** @return list<int> */
    public function activeIdsForClass(int $classId): array
    {
        return Student::query()->where('class_id', $classId)->where('status', 'active')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
