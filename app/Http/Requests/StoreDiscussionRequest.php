<?php

namespace App\Http\Requests;

use App\Models\Discussion;
use App\Models\Topic;
use App\Rules\SlateDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscussionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $topic = Topic::find($this->input('topic_id'));

        if (! $topic) {
            return true; // Let validation handle the missing topic_id
        }

        return $this->user()->can('create', [Discussion::class, $topic]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'exists:topics,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'array', new SlateDocument],
        ];
    }
}
