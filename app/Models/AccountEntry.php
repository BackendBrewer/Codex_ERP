<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;

class AccountEntry extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['entry_no', 'type', 'category', 'description', 'amount', 'entry_date', 'recorded_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'entry_date' => 'date'];
    }
}
