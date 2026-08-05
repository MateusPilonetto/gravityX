<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'media' => [
                'bail',
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4',
                'max:10240',
            ],
        ];
    }
}
