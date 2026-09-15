<?php

namespace App\Events\Models\Arrow;

use App\Events\Models\ModelChangedEvent;
use App\Models\Arrow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property Arrow $model
 */
class ArrowChangedEvent extends ModelChangedEvent
{
    public function __construct(
        Model                 $context,
        User                  $user,
        protected Arrow|Model $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'arrow-changed';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        /** @var Arrow $model */
        $model = $this->model;

        $broadcast = parent::broadcastWith();

        // Neither the raw vertices nor the computed coordinates are broadcast here - an arrow can
        // have enough vertices to push the payload over Reverb's message size cap (#3909).
        // Collaborating clients fetch the coordinates themselves via AjaxArrowController::show()
        // once they receive this event (see Arrow's changed.js).
        $modelArray = $model->toArray();
        unset($modelArray['polyline']['vertices_json']);
        $broadcast['model'] = $modelArray;

        return $broadcast;
    }
}
