<?php

namespace App\Repositories;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;

class AcademyDocumentRepository
{
    public function receipt(int $id): FeePayment
    {
        return FeePayment::query()->with(['student.academyClass', 'invoice'])->findOrFail($id);
    }

    public function challan(int $id): FeeInvoice
    {
        return FeeInvoice::query()->with(['student.academyClass', 'payments'])->findOrFail($id);
    }

    public function studentCard(int $id): Student
    {
        return Student::query()->with('academyClass')->findOrFail($id);
    }
}
