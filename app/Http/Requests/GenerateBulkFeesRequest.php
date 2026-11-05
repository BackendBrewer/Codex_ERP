<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateBulkFeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasFeature('fees.bulk') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'class_id' => ['nullable', 'integer', Rule::exists('academy_classes', 'id')->where('academy_id', $this->user()?->academy_id)],
        ];
    }
}
