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
            'import_string' => ['required', 'string', sprintf('max:%d', ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH)],
            // No exists rule: a draft deleted since the author confirmed is the stale case the service answers with a 409
            'discard_existing_draft_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
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

    /**
     * The id of the pending draft the author saw and confirmed may be discarded, if any. An id rather than a
     * model: a concurrent replacement may have deleted the draft it names.
     */
    public function getDiscardExistingDraftId(): ?int
    {
        $discardExistingDraftId = $this->validated('discard_existing_draft_id');

        return $discardExistingDraftId === null ? null : (int)$discardExistingDraftId;
    }
}
