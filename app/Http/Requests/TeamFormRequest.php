<?php

namespace App\Http\Requests;

use App\Models\Team;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class TeamFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
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
            'logo'        => ['nullable',
                              File::image()
                                  ->min(1)
                                  ->max(500)
                                  ->dimensions(Rule::dimensions()->maxWidth(512)->maxHeight(512))
                                  ->extensions(['jpg', 'jpeg', 'png']),
            ],
        ];
    }
}
