<?php

namespace App\Http\Requests\Tag;

use App\Models\Laratrust\Role;
use App\Models\Tags\TagCategory;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APITagFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->hasRole(Role::ROLE_ALL) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'context'       => 'required|string',
            'context_class' => 'required|in:team,user',
            'category'      => [Rule::in(TagCategory::all()->pluck(['name']))],
            'model_id'      => 'required|string',
            'name'          => 'required|string',
        ];
    }
}
