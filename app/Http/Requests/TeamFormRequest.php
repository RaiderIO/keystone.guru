<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var Team $team */
        $team = $this->route()->parameter('team');

        if ($team === null) {
            $nameRules = 'required|string|max:32|unique:teams';
        } else {
            $nameRules = Rule::unique('teams')->ignore($team);
        }

        return [
            'name'        => $nameRules,
            'description' => 'string|nullable',
            'logo'        => 'image|mimes:png|max:256'
        ];
    }
}
