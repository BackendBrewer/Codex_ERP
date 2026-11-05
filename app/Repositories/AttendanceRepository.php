<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;
use App\Models\Examination;

class AttendanceRepository
{
    public function saveExamAttendance(Examination $examination, int $studentId, string $status): AttendanceRecord
    {
        return AttendanceRecord::query()->updateOrCreate(
            ['student_id' => $studentId, 'attendance_date' => $examination->exam_date->toDateString()],
            ['status' => $status, 'note' => 'Exam: '.$examination->title.' · '.$examination->subject],
        );
    }
}
