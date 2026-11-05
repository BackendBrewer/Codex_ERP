<?php

namespace App\Repositories;

use App\Models\Examination;
use App\Models\ExamResult;

class ExamResultRepository
{
    public function saveMarks(Examination $examination, int $studentId, float $marks): ExamResult
    {
        return ExamResult::query()->updateOrCreate(
            ['examination_id' => $examination->id, 'student_id' => $studentId],
            ['marks_obtained' => $marks],
        );
    }
}
