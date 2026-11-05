<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResultSmsDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasFeature('results') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:academy_classes,id'],
            'month' => ['required', 'date_format:Y-m'],
            'message' => ['required', 'string', 'max:500'],
        ];
    }
}
