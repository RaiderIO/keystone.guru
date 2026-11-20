<?php

namespace App\Http\Requests\Api\V1\WalkGraph;

use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\WalkGraph\WalkGraph;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Override;

/**
 * Resolves the `{uiMapId}` route parameter to its walk graph.
 */
class APIWalkGraphShowRequest extends APIFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @throws ModelNotFoundException<WalkGraph>
     */
    public function getWalkGraph(): WalkGraph
    {
        return once(function (): WalkGraph {
            $uiMapId   = (int)$this->route('uiMapId');
            $walkGraph = app(WalkGraphRepositoryInterface::class)->findByUiMapIdWithFloors($uiMapId);

            if ($walkGraph === null) {
                throw new ModelNotFoundException()->setModel(WalkGraph::class, [$uiMapId]);
            }

            return $walkGraph;
        });
    }

    #[Override]
    protected function getRequestDtoClass(): ?string
    {
        return null;
    }
}
