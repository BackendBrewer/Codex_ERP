<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustFeeBillRequest;
use App\Http\Requests\FeeReportRequest;
use App\Http\Requests\GenerateBulkFeesRequest;
use App\Http\Requests\ModuleRecordRequest;
use App\Http\Requests\SaveFeeHeadRequest;
use App\Models\FeePayment;
use App\Services\AcademyDocumentService;
use App\Services\AcademyModuleListingService;
use App\Services\AcademyModuleRecordService;
use App\Services\AcademyReportingService;
use App\Services\FeeHeadService;
use App\Services\FeeWorkspaceService;
use App\Services\StudentWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademyModuleController extends Controller
{
    public function __construct(
        private readonly AcademyModuleRecordService $moduleRecords,
        private readonly FeeHeadService $feeHeadService,
        private readonly AcademyModuleListingService $moduleListing,
        private readonly AcademyReportingService $reporting,
        private readonly FeeWorkspaceService $feeWorkspace,
        private readonly AcademyDocumentService $documents,
        private readonly StudentWorkspaceService $studentWorkspace,
    ) {}

    public function index(string $module, Request $request): View
    {
        $this->authorizeModuleFeature($module, 'index');

        return $this->moduleListing->index($module, $request);
    }

    public function create(string $module): View
    {
        $this->authorizeModuleFeature($module, 'create');
        $definition = $this->moduleRecords->definition($module);
        $record = null;
        $options = $this->moduleListing->options($definition, $record, $module === 'fees');

        return view('modules.form', compact('module', 'definition', 'record', 'options'));
    }

    public function edit(string $module, int $id): View
    {
        $this->authorizeModuleFeature($module, 'edit');
        $definition = $this->moduleRecords->definition($module);
        $record = $this->moduleRecords->find($module, $id);
        $options = $this->moduleListing->options($definition, $record, $module === 'fees');

        return view('modules.form', compact('module', 'definition', 'record', 'options'));
    }

    public function store(string $module, ModuleRecordRequest $request): RedirectResponse
    {
        $this->authorizeModuleFeature($module, 'store');
        $definition = $this->moduleRecords->definition($module);
        $data = $this->moduleRecords->prepare($module, $request->validated());
        $createdPayment = $this->moduleRecords->store($module, $data, $request->user(), $request->file('photo'));

        if ($module === 'payments') {
            abort_unless($createdPayment instanceof FeePayment, 500, 'The payment receipt could not be opened.');

            return redirect()->route('receipts.print', $createdPayment->id);
        }
        if ($module === 'fees' && ! $request->user()->hasFeature('fees.invoices')) {
            return redirect()->route('dashboard')->with('success', $definition['singular'].' saved successfully.');
        }
        if ($module === 'fees') {
            return redirect()->route('modules.index', [
                'module' => 'fees',
                'student_id' => $data['student_id'],
                'month' => substr((string) $data['invoice_month'], 0, 7),
            ])->with('success', 'Fee bill created. Record a payment separately when money is received.');
        }

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' saved successfully.');
    }

    public function update(string $module, int $id, ModuleRecordRequest $request): RedirectResponse
    {
        $this->authorizeModuleFeature($module, 'edit');
        $definition = $this->moduleRecords->definition($module);
        $record = $this->moduleRecords->find($module, $id);
        $data = $this->moduleRecords->prepare($module, $request->validated());
        $this->moduleRecords->saveChanges($module, $record, $data, $request->user(), $request->file('photo'));

        if ($module === 'fees' && ! $request->user()->hasFeature('fees.invoices')) {
            return redirect()->route('dashboard')->with('success', $definition['singular'].' updated successfully.');
        }

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' updated successfully.');
    }

    public function destroy(string $module, int $id): RedirectResponse
    {
        $this->authorizeModuleFeature($module, 'edit');
        $definition = $this->moduleRecords->definition($module);
        $this->moduleRecords->remove($module, $this->moduleRecords->find($module, $id));

        return redirect()->route('modules.index', $module)->with('success', $definition['singular'].' deleted.');
    }

    public function admitInquiry(int $id): RedirectResponse
    {
        $this->requireFeature('inquiries');
        $this->moduleRecords->admitInquiry($id);

        return redirect()->route('modules.index', 'students')->with('success', 'Inquiry admitted. A student profile was created with the selected class fee.');
    }

    public function printReceipt(int $id, Request $request): View
    {
        $this->requireFeature('fees.receipts');

        return $this->documents->receipt($id, $request->user()->academy);
    }

    public function printChallan(int $id, Request $request): View
    {
        $this->requireFeature('fees.invoices');

        return $this->documents->challan($id, $request->user()->academy);
    }

    public function adjustFeeBill(int $id, AdjustFeeBillRequest $request): RedirectResponse
    {
        $studentId = $this->moduleRecords->adjustFeeBill($id, $request->validated());

        return redirect()->route('modules.index', ['module' => 'fees', 'student_id' => $studentId])
            ->with('success', 'Fee bill adjusted and its balance updated.');
    }

    public function printStudentCard(int $id, Request $request): View
    {
        $this->authorizeModuleFeature('students', 'index');

        return $this->documents->studentCard($id, $request->user()->academy);
    }

    public function studentWorkspace(int $id, Request $request): View
    {
        $this->authorizeModuleFeature('students', 'index');

        return $this->studentWorkspace->show($id, $request->user());
    }

    public function reports(FeeReportRequest $request): View
    {
        return $this->reporting->reports($request);
    }

    public function storeFeeHead(string $scope, SaveFeeHeadRequest $request): RedirectResponse
    {
        $this->feeHeadService->create($scope, $request->validated());

        return redirect()->route('fee-heads.index', $scope)->with('success', 'Extra charge added. It will be included in future bills for the students it is assigned to.');
    }

    public function updateFeeHead(string $scope, int $id, SaveFeeHeadRequest $request): RedirectResponse
    {
        $this->feeHeadService->update($scope, $id, $request->validated());

        return redirect()->route('fee-heads.index', $scope)->with('success', 'Extra charge updated. Existing bills keep their saved totals.');
    }

    public function deleteFeeHead(string $scope, int $id): RedirectResponse
    {
        $this->requireFeature('fee_heads.'.$scope);
        $this->feeHeadService->delete($scope, $id);

        return redirect()->route('fee-heads.index', $scope)->with('success', 'Extra charge removed. Previous bills are unchanged.');
    }

    public function feeHeads(string $scope, Request $request): View
    {
        $this->requireFeature('fee_heads.'.$scope);

        return $this->feeWorkspace->feeHeads($scope, $request);
    }

    public function bulkFeeForm(): View
    {
        $this->requireFeature('fees.bulk');

        return $this->feeWorkspace->bulkFeeForm();
    }

    public function monthlyFeeStudents(Request $request): View
    {
        $this->requireFeature('fees.monthly_students');

        return $this->feeWorkspace->monthlyFeeStudents($request);
    }

    public function feeDesk(Request $request): View
    {
        return $this->feeWorkspace->feeDesk($request);
    }

    public function generateBulkFees(GenerateBulkFeesRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->moduleRecords->generateBulkFees($data);
        $destination = $request->user()->hasFeature('fees.invoices')
            ? route('modules.index', ['module' => 'fees', 'month' => $data['invoice_month'], 'class_id' => $data['class_id'] ?? null])
            : route('fees.desk');
        $message = $result['created'] > 0
            ? $result['created'].' fee bill(s) created. Existing bills for that month were left unchanged.'
            : 'No new bills were needed. Existing bills for that month were left unchanged.';

        return redirect($destination)->with('success', $message);
    }

    private function authorizeModuleFeature(string $module, string $action): void
    {
        $feature = config('academy_access.module_features.'.$module);
        if ($module === 'fees') {
            $feature = in_array($action, ['create', 'store'], true) ? 'fees.single' : (in_array($action, ['edit'], true) ? 'fees.modify' : 'fees.invoices');
        } elseif ($module === 'payments') {
            $feature = in_array($action, ['create', 'store'], true) ? 'fees.collect' : 'fees.receipts';
        }
        if ($module === 'fees' && $action === 'edit') {
            abort_unless(auth()->user()?->hasFeature('fees.modify') || auth()->user()?->hasFeature('fees.reports.adjust'), 403, 'Fee bill editing has not been enabled for your academy.');

            return;
        }
        $this->requireFeature((string) $feature);
    }

    private function requireFeature(string $feature): void
    {
        abort_unless(auth()->user()?->hasFeature($feature), 403, 'This module option has not been enabled for your academy.');
    }

    /** @return array<string, mixed> */
}
