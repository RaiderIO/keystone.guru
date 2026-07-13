<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminDungeonRouteIndexRequest extends FormRequest
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
        return [
            'dungeon_id'         => ['nullable', 'integer'],
            'published_state_id' => ['nullable', 'integer', Rule::in(array_values(PublishedState::ALL))],
            'author'             => ['nullable', 'string', 'max:255'],
            'public_key'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
