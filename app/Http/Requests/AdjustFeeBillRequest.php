<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustFeeBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($this->user()?->hasFeature('fees.reports.adjust') ?? false) || ($this->user()?->hasFeature('fees.modify') ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operation' => ['required', Rule::in(['add', 'subtract'])],
            'adjustment' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'reason' => ['nullable', 'string', 'max:180'],
        ];
    }
}
