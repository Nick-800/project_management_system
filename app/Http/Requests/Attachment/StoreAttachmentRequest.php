<?php

namespace App\Http\Requests\Attachment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'on' => ['required', 'string', 'in:project,task'],
            'id' => ['required', 'integer'],
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimetypes:image/jpeg,image/png,application/pdf,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'on.in' => 'Attachments must target either a project or a task.',
            'file.max' => 'Attachments may not be larger than 10 MB.',
            'file.mimetypes' => 'Unsupported file type. Allowed: JPEG, PNG, PDF, TXT, DOCX.',
        ];
    }
}
