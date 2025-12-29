<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YouTubeAllowlistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return false;
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
            'channel_id'     => 'required|string',
            'release_claims' => 'nullable|in:0,1',
            'note'           => 'nullable|string',
        ];
    }

   public function messages(): array
    {
        return [
            'channel_id.required' => 'Channel ID is required.',
            'release_claims.in'   => 'Release claims must be either 0 or 1.',
        ];
    }
}
