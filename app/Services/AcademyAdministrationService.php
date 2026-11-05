<?php

namespace App\Services;

use App\Models\Academy;
use App\Repositories\AcademyAdministrationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademyAdministrationService
{
    public function __construct(private readonly AcademyAdministrationRepository $academies) {}

    /** @return array<string, mixed> */
    public function listing(string $search, string $sort, string $direction, bool $print): array
    {
        $search = trim($search);
        $sort = in_array($sort, ['name', 'city', 'province', 'contact_email', 'is_active'], true) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $hasSearch = $search !== '';
        $query = $this->academies->listingQuery($search, $sort, $direction);
        if ($print) {
            abort_unless($hasSearch, 422, 'Search academies before printing.');
            $academies = $query->get();

            return compact('academies', 'search');
        }
        $academies = $query->paginate(20)->withQueryString();
        $activeCount = $this->academies->activeCount($search);
        $academyChoices = $this->academies->allChoices()->map(fn (Academy $academy): array => [
            'value' => (string) $academy->id,
            'query' => $academy->name,
            'label' => collect([$academy->name, $academy->city, $academy->province, $academy->administrator?->name, $academy->administrator?->email])->filter()->unique()->implode(' · '),
        ]);

        return compact('academies', 'activeCount', 'search', 'hasSearch', 'sort', 'direction', 'academyChoices');
    }

    /** @return array<string, mixed> */
    public function createFormData(): array
    {
        return ['academy' => null, 'featureGroups' => config('academy_access.groups')];
    }

    /** @return array<string, mixed> */
    public function editFormData(Academy $academy): array
    {
        return ['academy' => $academy->load('administrator'), 'featureGroups' => config('academy_access.groups')];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $logoPath = ($data['logo'] ?? null)?->store('academy-logos', 'public');
        $letterheadPath = ($data['letterhead'] ?? null)?->store('academy-letterheads', 'public');
        $adminPhotoPath = ($data['admin_photo'] ?? null)?->store('academy-admins', 'public');
        $name = $data['name'];
        DB::transaction(function () use ($data, $logoPath, $letterheadPath, $adminPhotoPath, $name): void {
            $this->academies->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
                'logo_path' => $logoPath,
                'letterhead_path' => $letterheadPath,
                'enabled_features' => array_values(array_unique($data['enabled_features'] ?? [])),
            ], [
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => 'admin',
                'photo_path' => $adminPhotoPath,
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Academy $academy, array $data): void
    {
        $data['enabled_features'] = array_values(array_unique($data['enabled_features'] ?? []));
        $data['slug'] = $this->uniqueSlug($data['name'], $academy->id);
        $oldLogoPath = $academy->logo_path;
        $oldLetterheadPath = $academy->letterhead_path;
        $logoChanged = isset($data['logo']);
        $letterheadChanged = isset($data['letterhead']);
        $adminPhotoChanged = isset($data['admin_photo']);
        if ($logoChanged) {
            $data['logo_path'] = $data['logo']->store('academy-logos', 'public');
        }
        if ($letterheadChanged) {
            $data['letterhead_path'] = $data['letterhead']->store('academy-letterheads', 'public');
        }
        $adminPhoto = $data['admin_photo'] ?? null;
        unset($data['logo'], $data['letterhead'], $data['admin_photo']);
        $this->academies->update($academy, $data);
        if ($logoChanged && $oldLogoPath !== null) {
            Storage::disk('public')->delete($oldLogoPath);
        }
        if ($letterheadChanged && $oldLetterheadPath !== null) {
            Storage::disk('public')->delete($oldLetterheadPath);
        }
        $administrator = $academy->administrator;
        if ($adminPhotoChanged && $administrator !== null && $adminPhoto !== null) {
            $oldPhotoPath = $administrator->photo_path;
            $administrator->update(['photo_path' => $adminPhoto->store('academy-admins/'.$academy->id, 'public')]);
            if ($oldPhotoPath !== null) {
                Storage::disk('public')->delete($oldPhotoPath);
            }
        }
    }

    public function toggleStatus(Academy $academy): string
    {
        $academy->update(['is_active' => ! $academy->is_active]);

        return $academy->is_active ? 'Academy activated.' : 'Academy suspended. Its admin can no longer access the ERP.';
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'academy';
        $slug = $baseSlug;
        while ($this->academies->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
