<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AcademyController extends Controller
{
    public function index(): View
    {
        $academies = Academy::query()->with('administrator')->withCount('users')->orderBy('name')->paginate(20);
        $activeCount = Academy::query()->where('is_active', true)->count();

        return view('superadmin.academies.index', compact('academies', 'activeCount'));
    }

    public function create(): View
    {
        return view('superadmin.academies.form', ['academy' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        DB::transaction(function () use ($data): void {
            $academy = Academy::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
            ]);

            $academy->users()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => 'admin',
            ]);
        });

        return redirect()->route('superadmin.academies')->with('success', 'Academy created and its administrator account is ready.');
    }

    public function edit(Academy $academy): View
    {
        $academy->load('administrator');

        return view('superadmin.academies.form', compact('academy'));
    }

    public function update(Academy $academy, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['slug'] = $this->uniqueSlug($data['name'], $academy->id);
        $academy->update($data);

        return redirect()->route('superadmin.academies')->with('success', 'Academy details updated.');
    }

    public function toggleStatus(Academy $academy): RedirectResponse
    {
        $academy->update(['is_active' => ! $academy->is_active]);

        return redirect()->route('superadmin.academies')->with('success', $academy->is_active ? 'Academy activated.' : 'Academy suspended. Its admin can no longer access the ERP.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'academy';
        $slug = $baseSlug;
        while (Academy::query()->where('slug', $slug)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $baseSlug.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
