<?php

namespace App\Http\Requests;

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Auth check if needed
    }

    public function rules()
    {
        return [
            'target_id' => 'required|integer',
            'frequency' => 'required|string',
            'content'   => 'required|string',
            'scheduled_at' => 'nullable|date'
        ];
    }
}
