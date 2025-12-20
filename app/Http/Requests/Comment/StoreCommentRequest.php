<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'body' => ['required', 'string'],
        ];
    }
}
