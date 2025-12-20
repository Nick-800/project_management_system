<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'updates' => ['required', 'array', 'min:1'],
            'updates.*.id' => ['required', 'integer', 'exists:tasks,id'],
            'updates.*.status' => ['required', 'string', 'in:todo,in_progress,done,blocked'],
        ];
    }
}
