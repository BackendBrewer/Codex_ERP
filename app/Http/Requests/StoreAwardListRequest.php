<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAwardListRequest extends FormRequest
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
        return [
            'class_id' => ['required', 'integer', 'exists:academy_classes,id'],
            'subject' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:120'],
            'month' => ['required', 'date_format:Y-m'],
            'exam_date' => ['required', 'date'],
            'total_marks' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'passing_marks' => ['required', 'numeric', 'min:0', 'lte:total_marks'],
        ];
    }
}
