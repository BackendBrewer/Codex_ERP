<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'academy_name', 'email', 'phone', 'message', 'recaptcha_score', 'status'];

    protected function casts(): array
    {
        return ['recaptcha_score' => 'decimal:2'];
    }
}
