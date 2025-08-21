<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimetypes:' . implode(',', config('assets.allowed_file_types')),
                function ($attribute, $value, $fail) {
                    $maxSizeKb = config('assets.max_file_sizes_kb.' . $value->getMimeType(), config('assets.max_file_sizes_kb.default'));
                    if ($value->getSize() / 1024 > $maxSizeKb) {
                        $fail("The {$attribute} must not be greater than " . ($maxSizeKb / 1024) . " MB for this file type.");
                    }
                },
            ],
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    
}
