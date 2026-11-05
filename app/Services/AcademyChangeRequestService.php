<?php

namespace App\Services;

use App\Models\AcademyChangeRequest;
use App\Models\User;
use App\Repositories\AcademyChangeRequestRepository;
use Illuminate\Support\Collection;

class AcademyChangeRequestService
{
    public function __construct(private readonly AcademyChangeRequestRepository $requests) {}

    /** @return array{requests: Collection, openCount: int} */
    public function indexData(): array
    {
        return ['requests' => $this->requests->latestWithParties(), 'openCount' => $this->requests->countOpen()];
    }

    /** @param array{status: string, admin_response?: string|null} $data */
    public function review(AcademyChangeRequest $changeRequest, array $data, User $reviewer): void
    {
        $this->requests->update($changeRequest, [
            ...$data,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
