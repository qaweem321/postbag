<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'double_opt_in' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['double_opt_in' => $this->boolean('double_opt_in')]);
    }
}
