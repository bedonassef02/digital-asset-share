<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDownloadAssetsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_ids' => 'array',
            'asset_ids.*' => 'integer|exists:assets,id',
            'collection_ids' => 'array',
            'collection_ids.*' => 'integer|exists:collections,id',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty($this->asset_ids) && empty($this->collection_ids)) {
                $validator->errors()->add('asset_ids', 'At least one asset ID or collection ID is required for bulk download.');
                $validator->errors()->add('collection_ids', 'At least one asset ID or collection ID is required for bulk download.');
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'asset_ids.array' => 'Asset IDs must be provided as an array.',
            'asset_ids.*.integer' => 'Each asset ID must be an integer.',
            'asset_ids.*.exists' => 'One or more provided asset IDs do not exist.',
            'collection_ids.array' => 'Collection IDs must be provided as an array.',
            'collection_ids.*.integer' => 'Each collection ID must be an integer.',
            'collection_ids.*.exists' => 'One or more provided collection IDs do not exist.',
        ];
    }
}
