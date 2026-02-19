<?php

namespace App\Http\Requests;

use App\Rules\SlateDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscussionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', new SlateDocument],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }
}
