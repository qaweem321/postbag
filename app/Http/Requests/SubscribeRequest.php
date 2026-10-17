<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'name' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'alpha_dash', 'max:32'],
            // Skipping double opt-in is for addresses that already proved they own the inbox.
            'skip_confirmation' => ['sometimes', 'boolean'],
        ];
    }
}
