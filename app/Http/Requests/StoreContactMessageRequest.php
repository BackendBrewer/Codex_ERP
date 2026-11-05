<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        if ($this->filled('website')) {
            return ['website' => ['nullable', 'string', 'max:255']];
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'academy_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:15', 'max:5000'],
            'recaptcha_token' => ['required', 'string', 'max:4096'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }
}
