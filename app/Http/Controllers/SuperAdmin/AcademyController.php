<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademyRequest;
use App\Http\Requests\UpdateAcademyRequest;
use App\Models\Academy;
use App\Services\AcademyAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademyController extends Controller
{
    public function __construct(private readonly AcademyAdministrationService $administration) {}

    public function index(Request $request): View
    {
        $data = $this->administration->listing(
            (string) $request->query('q', ''),
            (string) $request->query('sort', 'name'),
            (string) $request->query('direction', 'asc'),
            $request->boolean('print'),
        );

        return view($request->boolean('print') ? 'documents.academies-print' : 'superadmin.academies.index', $data);
    }

    public function create(): View
    {
        return view('superadmin.academies.form', $this->administration->createFormData());
    }

    public function store(StoreAcademyRequest $request): RedirectResponse
    {
        $this->administration->create($request->validated());

        return redirect()->route('superadmin.academies')->with('success', 'Academy created and its administrator account is ready.');
    }

    public function edit(Academy $academy): View
    {
        return view('superadmin.academies.form', $this->administration->editFormData($academy));
    }

    public function update(Academy $academy, UpdateAcademyRequest $request): RedirectResponse
    {
        $this->administration->update($academy, $request->validated());

        return redirect()->route('superadmin.academies')->with('success', 'Academy details updated.');
    }

    public function toggleStatus(Academy $academy): RedirectResponse
    {
        return redirect()->route('superadmin.academies')->with('success', $this->administration->toggleStatus($academy));
    }
}
