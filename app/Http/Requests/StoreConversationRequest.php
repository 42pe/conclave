<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->is_suspended;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => [
                'required',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ((int) $value === $this->user()->id) {
                        $fail('You cannot start a conversation with yourself.');
                    }
                },
            ],
            'body' => ['required', 'array', new \App\Rules\SlateDocument],
        ];
    }
}
