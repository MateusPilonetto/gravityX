<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'caption' => $this->normalizeNullableString($this->input('caption')),
            'body' => $this->normalizeNullableString($this->input('body')),
        ]);
    }

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
            'caption' => ['nullable', 'string', 'max:255', 'required_without_all:body,image'],
            'body' => ['nullable', 'string', 'max:5000', 'required_without_all:caption,image'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
                'required_without_all:caption,body',
            ],
        ];
    }

    private function normalizeNullableString(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
