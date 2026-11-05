<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFeeHeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $scope = (string) $this->route('scope');

        return in_array($scope, ['academy', 'class', 'student'], true) && ($this->user()?->hasFeature('fee_heads.'.$scope) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $scope = (string) $this->route('scope');
        $academyId = $this->user()?->academy_id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'class_id' => [$scope === 'class' ? 'required' : 'nullable', 'integer', Rule::exists('academy_classes', 'id')->where('academy_id', $academyId)],
            'student_id' => [$scope === 'student' ? 'required' : 'nullable', 'integer', Rule::exists('students', 'id')->where('academy_id', $academyId)],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
