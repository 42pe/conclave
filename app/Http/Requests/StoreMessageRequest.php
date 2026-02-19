<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Rules\SlateDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $conversation = Conversation::find($this->input('conversation_id'));

        if (! $conversation) {
            return true; // Let validation handle it
        }

        return $this->user()->can('sendMessage', $conversation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'exists:conversations,id'],
            'body' => ['required', 'array', new SlateDocument],
        ];
    }
}
