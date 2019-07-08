<?php

namespace App\Http\Requests;

use App\Models\Expansion;
use App\Models\Release;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->hasRole("admin");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var Release $release */
        $release = $this->route()->parameter('release');
        $rules = [
            'version' => ['required', Rule::unique('releases', 'version')->ignore($release->id)]
        ];
        return $rules;
    }
}
