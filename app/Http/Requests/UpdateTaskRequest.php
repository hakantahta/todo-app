<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_completed' => ['nullable', 'boolean'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}


