<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

class AdminToolsCombatLogRunDataPruneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'seasons'   => ['required', 'array', 'min:1'],
            'seasons.*' => ['required', 'string', 'max:255'],
            'min_id'    => ['required', 'integer', 'min:0'],
            'max_id'    => ['required', 'integer', 'min:0'],
        ];
    }
}
