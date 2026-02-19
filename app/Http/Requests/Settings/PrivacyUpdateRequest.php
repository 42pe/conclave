<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrivacyUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'show_real_name' => ['required', 'boolean'],
            'show_email' => ['required', 'boolean'],
            'show_in_directory' => ['required', 'boolean'],
        ];
    }
}
