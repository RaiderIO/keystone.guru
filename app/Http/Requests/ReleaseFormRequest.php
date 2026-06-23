<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use App\Models\Release;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        /** @var Release|null $release */
        $release = $this->route()->parameter('release');

        $rule = Rule::unique('releases', 'version');
        if ($release !== null) {
            $rule->ignore($release->id);
        }

        $rules = [
            'version' => [
                'required',
                $rule,
            ],
        ];

        return $rules;
    }
}
