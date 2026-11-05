<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAwardListRequest;
use App\Http\Requests\StoreResultSmsDraftRequest;
use App\Http\Requests\UpdateAwardMarksRequest;
use App\Models\Examination;
use App\Models\Student;
use App\Services\ExaminationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationWorkflowController extends Controller
{
    public function __construct(private readonly ExaminationWorkflowService $workflow) {}

    public function index(Request $request): View
    {
        return view('examination.index', $this->workflow->workspace($request->user()));
    }

    public function store(StoreAwardListRequest $request): RedirectResponse
    {
        $examination = $this->workflow->createAwardList($request->user(), $request->validated());

        return redirect()->route('examination.award-lists.show', $examination)->with('success', 'Award list created. Enter marks and attendance on the printable sheet.');
    }

    public function awardList(Examination $examination, Request $request): View
    {
        return view('examination.award-list', ['examination' => $examination->load('academyClass')] + $this->workflow->awardList($request->user(), $examination));
    }

    public function updateMarks(Examination $examination, UpdateAwardMarksRequest $request): RedirectResponse
    {
        $this->workflow->saveMarks($request->user(), $examination, $request->validated());

        return redirect()->route('examination.award-lists.show', $examination)->with('success', 'Marks and attendance saved.');
    }

    public function printAwardList(Examination $examination, Request $request): View
    {
        return view('documents.award-list', [
            'examination' => $examination->load('academyClass'),
            'academy' => $request->user()->academy,
        ] + $this->workflow->printableAwardList($request->user(), $examination));
    }

    public function resultSheet(Request $request): View
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/D', $month) === 1, 422, 'Select a valid month.');
        $classId = $request->integer('class_id') ?: null;
        $data = $this->workflow->classResults($request->user(), $classId, $month) + [
            'classId' => $classId,
            'month' => $month,
            'academy' => $request->user()->academy,
        ];
        if ($request->boolean('print')) {
            abort_unless($classId !== null && $data['examinations']->isNotEmpty(), 422, 'Choose a class and month with processed results before printing.');

            return view('documents.result-sheet', $data);
        }

        return view('examination.result-sheet', $data);
    }

    public function resultCard(Student $student, Request $request): View
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/D', $month) === 1, 422, 'Select a valid month.');
        $data = $this->workflow->studentResultCard($request->user(), $student->load('academyClass'), $month) + [
            'student' => $student,
            'month' => $month,
            'academy' => $request->user()->academy,
        ];

        return view('documents.result-card', $data);
    }

    public function sendResultSms(StoreResultSmsDraftRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $recipients = $this->workflow->createResultSmsDraft($request->user(), $data);

        return redirect()->route('examination.result-sheet', ['class_id' => $data['class_id'], 'month' => $data['month']])
            ->with('success', 'Result SMS draft saved for '.$recipients.' parents. Connect an SMS provider to deliver it.');
    }
}
