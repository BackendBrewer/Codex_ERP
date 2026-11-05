<?php

namespace App\Repositories;

use App\Models\MessageLog;

class MessageLogRepository
{
    public function createResultDraft(string $message, int $recipientCount, int $createdBy): MessageLog
    {
        return MessageLog::query()->create([
            'audience' => 'class',
            'message' => $message,
            'recipient_count' => $recipientCount,
            'provider' => 'manual',
            'status' => 'draft',
            'created_by' => $createdBy,
        ]);
    }
}
