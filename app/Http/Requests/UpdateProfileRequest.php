<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isSuperAdmin() || ($user->role === 'admin' && $user->academy !== null));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        if ($this->user()?->isSuperAdmin()) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'new_password' => ['nullable', 'string', 'min:12', 'confirmed'],
            ];
        }

        return ['academy_name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:1000']];
    }
}
