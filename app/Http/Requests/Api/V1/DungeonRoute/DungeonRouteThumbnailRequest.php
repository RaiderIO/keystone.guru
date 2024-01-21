<?php

namespace App\Http\Requests\Api\V1\DungeonRoute;

use App\Http\Requests\Api\V1\APIFormRequest;
use Auth;
use Illuminate\Validation\Rule;

class DungeonRouteThumbnailRequest extends APIFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'public_key' => Rule::exists('dungeon_routes', 'public_key')->where('user_id', Auth::id()),
            'width'      => 'nullable|int',
            'height'     => 'nullable|int',
            'quality'    => 'nullable|int',
        ];
    }
}
