<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAwardMarksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasFeature('exams') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $examination = $this->route('examination');

        return [
            'marks' => ['required', 'array'],
            'marks.*' => ['required', 'numeric', 'min:-1', 'max:'.($examination->total_marks ?? 999999.99)],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:present,absent,leave'],
        ];
    }
}
