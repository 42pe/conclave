<?php

namespace App\Http\Requests;

use App\Models\Reply;
use App\Rules\SlateDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', new SlateDocument],
            'parent_id' => ['nullable', 'integer', 'exists:replies,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($this->parent_id) {
                $parent = Reply::find($this->parent_id);

                if ($parent && $parent->discussion_id !== $this->route('discussion')->id) {
                    $validator->errors()->add('parent_id', 'The parent reply must belong to the same discussion.');
                }

                if ($parent && $parent->depth >= 2) {
                    $validator->errors()->add('parent_id', 'Maximum reply nesting depth has been reached.');
                }
            }
        });
    }
}
