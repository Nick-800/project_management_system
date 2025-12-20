<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('tag')?->id ?? null;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:tags,slug,'.($id ?? 'NULL').',id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
