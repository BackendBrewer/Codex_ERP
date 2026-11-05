<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $feature = match ($this->query('fee_report')) {
            'pending' => 'fees.reports.pending',
            'date' => 'fees.reports.collection_date',
            'month' => 'fees.reports.collection_month',
            'summary' => 'fees.reports.summary_date',
            'recovery' => 'fees.reports.recovery',
            'defaulters' => 'fees.defaulters',
            'adjust' => 'fees.reports.adjust',
            default => 'reports.dashboard',
        };

        return $this->user()?->hasFeature($feature) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $academyId = $this->user()?->academy_id;

        return [
            'fee_report' => ['nullable', Rule::in(['pending', 'date', 'month', 'summary', 'recovery', 'defaulters', 'adjust'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')->where('academy_id', $academyId)],
            'class_id' => ['nullable', 'integer', Rule::exists('academy_classes', 'id')->where('academy_id', $academyId)],
            'print' => ['nullable', 'boolean'],
        ];
    }
}
