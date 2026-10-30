<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademy;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use BelongsToAcademy;

    protected $fillable = ['employee_no', 'name', 'phone', 'cnic', 'role', 'department', 'monthly_salary', 'joining_date', 'status'];

    protected function casts(): array
    {
        return ['monthly_salary' => 'decimal:2', 'joining_date' => 'date'];
    }
}
