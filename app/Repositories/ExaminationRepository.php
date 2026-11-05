<?php

namespace App\Repositories;

use App\Models\AcademyClass;
use App\Models\Examination;
use App\Models\ExamResult;
use Illuminate\Database\Eloquent\Collection;

class ExaminationRepository
{
    /** @return Collection<int, AcademyClass> */
    public function activeClasses(): Collection
    {
        return AcademyClass::query()->where('active', true)->orderBy('name')->orderBy('section')->get();
    }

    /** @return Collection<int, Examination> */
    public function recentAwardLists(): Collection
    {
        return Examination::query()->with('academyClass')->latest('exam_date')->latest()->limit(30)->get();
    }

    public function createAwardList(array $attributes): Examination
    {
        return Examination::query()->create($attributes);
    }

    public function findClass(int $classId): AcademyClass
    {
        return AcademyClass::query()->findOrFail($classId);
    }

    public function hasProcessedResultsForMonth(int $classId, string $year, string $month): bool
    {
        return ExamResult::query()->whereHas('examination', fn ($query) => $query->where('class_id', $classId)->whereYear('exam_date', $year)->whereMonth('exam_date', $month))->exists();
    }

    /** @return Collection<int, Examination> */
    public function awardListsForMonth(int $classId, string $year, string $month): Collection
    {
        return Examination::query()->where('class_id', $classId)->whereYear('exam_date', $year)->whereMonth('exam_date', $month)
            ->with('results')->orderBy('subject')->orderBy('title')->get();
    }
}
