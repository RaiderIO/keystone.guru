<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpansionFormRequest extends FormRequest
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
        $id = $this->route()->parameter('id');
        $rules = [
            'name' => ['required', Rule::unique('expansions')->ignore($id)],
            'color' => 'required'
        ];
        // Icon is required when making a new expansion, when editing it's optional
        if($id === null){
            $rules['icon'] = 'required|image|mimes:png|max:128';
        }
        return $rules;
    }
}
