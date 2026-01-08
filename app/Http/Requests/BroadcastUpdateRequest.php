<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'target_id' => 'integer|nullable',
            'frequency' => 'string|nullable',
            'content'   => 'string|nullable',
            'scheduled_at' => 'nullable|date'
            
        ];
    }
}
