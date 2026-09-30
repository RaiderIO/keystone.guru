<?php

namespace App\Http\Requests\DungeonRoute;

use App\Http\Requests\MDT\ImportStringFormRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonRouteImportMdtStringFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // The controller authorizes against the bound route
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'import_string'          => ['required', 'string', sprintf('max:%d', ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH)],
            'discard_existing_draft' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_string.required' => __('validation.custom.mdt_import_string.required'),
            'import_string.max'      => __('validation.custom.mdt_import_string.max', ['max' => ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH]),
        ];
    }

    public function isDiscardExistingDraft(): bool
    {
        return $this->boolean('discard_existing_draft');
    }
}
