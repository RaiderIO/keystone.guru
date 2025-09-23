<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserReportFormRequest extends FormRequest
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
            'category' => 'required|max:255',
            // Required when not logged in
            'name'       => !Auth::check() ? 'required' : '',
            'contact_ok' => 'bool',
            'message'    => 'required|max:1000',
        ];
    }
}
