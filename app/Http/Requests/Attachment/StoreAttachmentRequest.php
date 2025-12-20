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
}
