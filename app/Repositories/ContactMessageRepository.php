<?php

namespace App\Repositories;

use App\Models\ContactMessage;

class ContactMessageRepository
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ContactMessage
    {
        return ContactMessage::query()->create($attributes);
    }
}
