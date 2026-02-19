<?php

namespace App\Http\Requests;

use App\Models\Discussion;
use App\Models\Reply;
use App\Rules\SlateDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $discussion = Discussion::find($this->input('discussion_id'));

        if (! $discussion) {
            return true; // Let validation handle the missing discussion_id
        }

        return $this->user()->can('create', [Reply::class, $discussion]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'discussion_id' => ['required', 'exists:discussions,id'],
            'parent_id' => ['nullable', 'exists:replies,id'],
            'body' => ['required', 'array', new SlateDocument],
        ];
    }
}
