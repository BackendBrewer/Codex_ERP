<?php

namespace App\Services;

use App\Models\Academy;
use App\Repositories\AcademyDocumentRepository;
use Illuminate\View\View;

class AcademyDocumentService
{
    public function __construct(private readonly AcademyDocumentRepository $documents) {}

    public function receipt(int $id, Academy $academy): View
    {
        return view('documents.receipt', ['document' => $this->documents->receipt($id), 'academy' => $academy]);
    }

    public function challan(int $id, Academy $academy): View
    {
        return view('documents.challan', ['document' => $this->documents->challan($id), 'academy' => $academy]);
    }

    public function studentCard(int $id, Academy $academy): View
    {
        return view('documents.student-cards', ['students' => collect([$this->documents->studentCard($id)]), 'academy' => $academy]);
    }
}
