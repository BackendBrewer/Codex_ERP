<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademyChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademyChangeRequestController extends Controller
{
    public function index(): View
    {
        $requests = AcademyChangeRequest::query()
            ->with(['academy', 'requester', 'reviewer'])
            ->latest()
            ->paginate(20);
        $openCount = AcademyChangeRequest::query()->whereIn('status', ['open', 'in_review'])->count();

        return view('superadmin.change-requests.index', compact('requests', 'openCount'));
    }

    public function update(AcademyChangeRequest $changeRequest, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'completed', 'declined'])],
            'admin_response' => ['nullable', 'string', 'max:2000'],
        ]);
        $changeRequest->update([
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Academy change request status updated.');
    }
}
