<?php

namespace App\Http\Requests;

use App\Rules\SlateDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', new SlateDocument],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($this->recipient_id == $this->user()->id) {
                $validator->errors()->add('recipient_id', 'You cannot start a conversation with yourself.');
            }

            if ($this->recipient_id) {
                $recipient = \App\Models\User::find($this->recipient_id);

                if ($recipient && $recipient->is_deleted) {
                    $validator->errors()->add('recipient_id', 'You cannot message a deleted user.');
                }
            }
        });
    }
}
