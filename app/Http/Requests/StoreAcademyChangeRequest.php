<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademyChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->academy_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['contact', 'branding', 'modules', 'other'])],
            'subject' => ['required', 'string', 'max:180'],
            'details' => ['required', 'string', 'max:3000'],
        ];
    }
}
