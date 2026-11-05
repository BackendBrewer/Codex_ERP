<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAcademyChangeRequestRequest;
use App\Models\AcademyChangeRequest;
use App\Services\AcademyChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademyChangeRequestController extends Controller
{
    public function __construct(private readonly AcademyChangeRequestService $changeRequests) {}

    public function index(): View
    {
        return view('superadmin.change-requests.index', $this->changeRequests->indexData());
    }

    public function update(AcademyChangeRequest $changeRequest, UpdateAcademyChangeRequestRequest $request): RedirectResponse
    {
        $this->changeRequests->review($changeRequest, $request->validated(), $request->user());

        return back()->with('success', 'Academy change request status updated.');
    }
}
