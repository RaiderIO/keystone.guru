<?php

namespace App\Http\Requests\Api\V1\Route;

use App\Dto\Request\Route\DungeonRouteThumbnailRequestDTO;
use App\Http\Requests\Api\V1\APIFormRequest;

/**
 * @method DungeonRouteThumbnailRequestDTO|null getDTO()
 */
class DungeonRouteThumbnailRequest extends APIFormRequest
{
    protected function getRequestDTOClass(): ?string
    {
        return DungeonRouteThumbnailRequestDTO::class;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'viewport_width'  => 'nullable|int|min:192|max:1620',
            'viewport_height' => 'nullable|int|min:128|max:1080',
            'image_width'     => 'nullable|int|min:192|max:1620',
            'image_height'    => 'nullable|int|min:128|max:1080',
            'zoom_level'      => 'nullable|numeric|min:1|max:5',
            'quality'         => 'nullable|int|min:1|max:100',
        ];
    }
}
