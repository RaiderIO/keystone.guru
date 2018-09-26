<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserReportFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->hasRole("user") || \Auth::user()->hasRole("admin");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'userreport_context' => 'required|max:255',
            'userreport_category' => 'required|max:255',
            // Required when not logged in
            'userreport_name' => Auth::user() === null ? 'required' : '',
            'userreport_message' => 'required|max:1000'
        ];
    }
}
