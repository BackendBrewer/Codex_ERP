<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $hasSearch = $search !== '';
        $sort = in_array($request->query('sort'), ['name', 'city', 'province', 'contact_email', 'is_active'], true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $query = Academy::query()->with('administrator')->withCount('users')
            ->when($hasSearch, fn ($builder) => $builder->where(function ($filter) use ($search): void {
                $filter->where('name', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('province', 'like', '%'.$search.'%')
                    ->orWhere('contact_email', 'like', '%'.$search.'%')
                    ->orWhere('contact_phone', 'like', '%'.$search.'%')
                    ->orWhereHas('administrator', fn ($admin) => $admin->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            }))
            ->when(! $hasSearch, fn ($builder) => $builder->whereRaw('1 = 0'))
            ->orderBy($sort, $direction);
        if ($request->boolean('print')) {
            abort_unless($hasSearch, 422, 'Search academies before printing.');
            $academies = $query->get();

            return view('documents.academies-print', compact('academies', 'search'));
        }
        $academies = $query->paginate(20)->withQueryString();
        $activeCount = (clone $query)->reorder()->where('is_active', true)->count();
        $academyChoices = Academy::query()->with('administrator')->orderBy('name')->get()->map(fn (Academy $academy): array => [
            'value' => (string) $academy->id,
            'query' => $academy->name,
            'label' => collect([$academy->name, $academy->city, $academy->province, $academy->administrator?->name, $academy->administrator?->email])->filter()->unique()->implode(' · '),
        ]);

        return view('superadmin.academies.index', compact('academies', 'activeCount', 'search', 'hasSearch', 'sort', 'direction', 'academyChoices'));
    }

    public function create(): View
    {
        return view('superadmin.academies.form', ['academy' => null, 'featureGroups' => config('academy_access.groups')]);
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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
            'admin_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'enabled_features' => ['nullable', 'array'],
            'enabled_features.*' => ['string', Rule::in($this->featureKeys())],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $logoPath = $request->file('logo')?->store('academy-logos', 'public');
            $adminPhotoPath = $request->file('admin_photo')?->store('academy-admins', 'public');
            $academy = Academy::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
                'logo_path' => $logoPath,
                'enabled_features' => array_values(array_unique($data['enabled_features'] ?? [])),
            ]);

            $academy->users()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => 'admin',
                'photo_path' => $adminPhotoPath,
            ]);
        });

        return redirect()->route('superadmin.academies')->with('success', 'Academy created and its administrator account is ready.');
    }

    public function edit(Academy $academy): View
    {
        $academy->load('administrator');

        return view('superadmin.academies.form', ['academy' => $academy, 'featureGroups' => config('academy_access.groups')]);
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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'admin_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'enabled_features' => ['nullable', 'array'],
            'enabled_features.*' => ['string', Rule::in($this->featureKeys())],
        ]);
        $data['enabled_features'] = array_values(array_unique($data['enabled_features'] ?? []));
        $data['slug'] = $this->uniqueSlug($data['name'], $academy->id);
        $oldLogoPath = $academy->logo_path;
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('academy-logos', 'public');
        }
        unset($data['logo'], $data['admin_photo']);
        $academy->update($data);
        if ($request->hasFile('logo') && $oldLogoPath !== null) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        $administrator = $academy->administrator;
        if ($administrator !== null && $request->hasFile('admin_photo')) {
            $oldPhotoPath = $administrator->photo_path;
            $administrator->update(['photo_path' => $request->file('admin_photo')->store('academy-admins/'.$academy->id, 'public')]);
            if ($oldPhotoPath !== null) {
                Storage::disk('public')->delete($oldPhotoPath);
            }
        }

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

    /** @return list<string> */
    private function featureKeys(): array
    {
        return collect(config('academy_access.groups'))->flatMap(fn (array $group): array => array_keys($group))->values()->all();
    }
}
