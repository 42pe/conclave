<?php

namespace App\Http\Requests\Admin;

use App\Enums\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'iso_code' => ['required', 'string', 'max:10', 'unique:locations,iso_code'],
            'type' => ['required', Rule::enum(LocationType::class)],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
