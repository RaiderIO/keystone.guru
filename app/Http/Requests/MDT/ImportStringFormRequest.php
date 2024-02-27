<?php

namespace App\Http\Requests\MDT;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ImportStringFormRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'import_string'       => 'required|string',
            'mdt_import_sandbox'  => 'bool',
            'import_as_this_week' => 'bool',
            // May be -1 (unset) or must be part of the user's teams
            'team_id'             => [Rule::in(
                array_merge(Auth::check() ? Auth::user()->teams->pluck('id')->toArray() : [], [null, -1])
            )],
        ];
    }
}
