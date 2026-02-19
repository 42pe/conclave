<?php

namespace App\Http\Requests\Admin;

use App\Enums\TopicVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'visibility' => ['required', Rule::enum(TopicVisibility::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
