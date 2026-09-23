<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Raises the published state of the collection's own less-visible routes to match the collection's. The request
 * carries no input - the collection itself decides which of its routes qualify, never the client.
 */
class AjaxDungeonRouteCollectionRoutesPublishFormRequest extends FormRequest
{
    /**
     * Checked before validation, so someone who may not edit the collection learns nothing about its routes.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit', $this->dungeonRouteCollection()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function dungeonRouteCollection(): DungeonRouteCollection
    {
        /** @var DungeonRouteCollection $dungeonRouteCollection */
        $dungeonRouteCollection = $this->route('dungeonRouteCollection');

        return $dungeonRouteCollection;
    }
}
