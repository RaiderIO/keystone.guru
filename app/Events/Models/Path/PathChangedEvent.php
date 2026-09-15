<?php

namespace App\Events\Models\Path;

use App\Events\Models\ModelChangedEvent;
use App\Models\MapIcon;
use App\Models\Path;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property MapIcon $model
 */
class PathChangedEvent extends ModelChangedEvent
{
    /**
     * @param Model      $context
     * @param User       $user
     * @param Path|Model $model
     */
    public function __construct(
        Model                $context,
        User                 $user,
        protected Path|Model $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'path-changed';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        /** @var Path $model */
        $model = $this->model;

        $broadcast = parent::broadcastWith();

        // Neither the raw vertices nor the computed coordinates are broadcast here - a path can
        // have enough vertices to push the payload over Reverb's message size cap (#3909).
        // Collaborating clients fetch the coordinates themselves via AjaxPathController::show()
        // once they receive this event (see Path's changed.js).
        $modelArray = $model->toArray();
        unset($modelArray['polyline']['vertices_json']);
        $broadcast['model'] = $modelArray;

        return $broadcast;
    }
}
