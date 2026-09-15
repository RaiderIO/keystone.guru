<?php

namespace App\Http\Requests\Tag;

use App\Models\Interfaces\HasTagsInterface;
use App\Models\Laratrust\Role;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Teapot\StatusCode;

class APITagFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->hasRole(Role::ROLE_ALL) ?? false;
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
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

    /**
     * @return Model&HasTagsInterface
     */
    public function getContext(): Model
    {
        return once(function (): Model {
            $contextPublicKey = $this->validated('context');

            /** @var Model&HasTagsInterface $context */
            $context = match ($this->validated('context_class')) {
                'user'  => User::where('public_key', $contextPublicKey)->firstOrFail(),
                'team'  => Team::where('public_key', $contextPublicKey)->firstOrFail(),
                default => abort(StatusCode::BAD_REQUEST, 'Invalid context class'),
            };

            return $context;
        });
    }
}
