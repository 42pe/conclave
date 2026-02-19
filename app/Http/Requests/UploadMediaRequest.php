<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,mp4,webm,pdf',
                'max:51200',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $file = $this->file('file');
            if (! $file) {
                return;
            }

            $mime = $file->getMimeType();
            $size = $file->getSize();

            if (str_starts_with($mime, 'image/') && $size > 5 * 1024 * 1024) {
                $validator->errors()->add('file', 'Image files must not exceed 5MB.');
            }

            if (str_starts_with($mime, 'video/') && $size > 50 * 1024 * 1024) {
                $validator->errors()->add('file', 'Video files must not exceed 50MB.');
            }

            if ($mime === 'application/pdf' && $size > 10 * 1024 * 1024) {
                $validator->errors()->add('file', 'Document files must not exceed 10MB.');
            }
        });
    }
}
