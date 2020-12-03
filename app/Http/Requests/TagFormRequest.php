<?php

namespace App\Http\Requests;

use App\Models\Tags\TagCategory;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->hasRole('user') || Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category' => [Rule::in(TagCategory::all()->pluck(['name']))],
            'model_id' => 'required|string',
            'name'     => 'required|string',
            'color'    => 'nullable|string',
        ];
    }
}
