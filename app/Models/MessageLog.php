<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['audience', 'message', 'recipient_count', 'provider', 'status', 'created_by'];
}
