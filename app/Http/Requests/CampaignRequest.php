<?php

namespace App\Http\Requests;

use App\Models\MailingList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'list' => ['required', 'string', Rule::exists('mailing_lists', 'uuid')],
            'subject' => ['required', 'string', 'max:200'],
            'from_name' => ['required', 'string', 'max:120'],
            'from_email' => ['required', 'string', 'email:rfc', 'max:254'],
            'body_markdown' => ['required', 'string', 'max:200000'],
        ];
    }

    /** @return array<string, mixed> */
    public function attributesForModel(): array
    {
        $data = $this->safe()->except('list');
        $data['mailing_list_id'] = MailingList::query()->where('uuid', $this->input('list'))->value('id');

        return $data;
    }
}
