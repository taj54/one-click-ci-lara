<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CIMigrateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow access, or implement authorization logic here
    }

    public function rules(): array
    {
        return [
            'uniqueId' => ['required', 'string', 'uuid'],
            'projectName' => ['required', 'string', 'min:3', 'max:255'],
            'laravelVersion' => [
                'required',
                'string',
                Rule::in(['10.x', '9.x', '8.x']),
            ],
            'installSail' => ['required', 'boolean'],
        ];
    }
    public function messages(): array
    {
        return [
            'projectName.required' => 'Please provide a project name.',
            'laravelVersion.in' => 'Only versions 10.x, 9.x, or 8.x are supported.',
        ];
    }
}
