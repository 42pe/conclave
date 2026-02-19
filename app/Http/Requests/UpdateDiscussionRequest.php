<?php

namespace App\Http\Requests;

use App\Rules\SlateDocument;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscussionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('discussion'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'array', new SlateDocument],
            'location_id' => ['nullable', 'exists:locations,id'],
        ];
    }
}
