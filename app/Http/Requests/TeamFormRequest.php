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

        $rules = [
            'name' => ['required', Rule::unique('teams')->ignore($team->id)],
            'description' => 'string'
        ];

        // Logo is required when making a new team, when editing it's optional
        if($team === null){
            $rules['logo'] = 'required|image|mimes:png|max:256';
        }

        return $rules;
    }
}
