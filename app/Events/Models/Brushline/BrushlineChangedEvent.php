<?php

namespace App\Events\Models\Brushline;

use App\Events\Models\ModelChangedEvent;
use App\Models\Brushline;
use App\Models\MapIcon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property MapIcon $model
 */
class BrushlineChangedEvent extends ModelChangedEvent
{
    /**
     * @param Model           $context
     * @param User            $user
     * @param Brushline|Model $model
     */
    public function __construct(
        Model                     $context,
        User                      $user,
        protected Brushline|Model $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'brushline-changed';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        /** @var Brushline $model */
        $model = $this->model;

        $broadcast = parent::broadcastWith();

        // Neither the raw vertices nor the computed coordinates are broadcast here - a brushline
        // can have enough vertices to push the payload over Reverb's message size cap (#3909).
        // Collaborating clients fetch the coordinates themselves via AjaxBrushlineController::show()
        // once they receive this event (see Brushline's changed.js).
        $modelArray = $model->toArray();
        unset($modelArray['polyline']['vertices_json']);
        $broadcast['model'] = $modelArray;

        return $broadcast;
    }
}
