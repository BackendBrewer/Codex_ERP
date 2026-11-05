<?php

namespace App\Services;

use App\Models\FeeHead;
use App\Repositories\FeeHeadRepository;

class FeeHeadService
{
    public function __construct(private readonly FeeHeadRepository $feeHeads) {}

    /** @param array<string, mixed> $data */
    public function create(string $scope, array $data): FeeHead
    {
        return $this->feeHeads->create($this->attributes($scope, $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $scope, int $id, array $data): void
    {
        $this->feeHeads->update($scope, $id, $this->attributes($scope, $data));
    }

    public function delete(string $scope, int $id): void
    {
        $this->feeHeads->delete($scope, $id);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function attributes(string $scope, array $data): array
    {
        $data['scope'] = $scope;
        $data['class_id'] = $scope === 'class' ? $data['class_id'] : null;
        $data['student_id'] = $scope === 'student' ? $data['student_id'] : null;
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }
}
