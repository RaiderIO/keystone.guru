<?php

namespace App\Events\Models\PridefulEnemy;

use App\Events\Models\ModelChangedEvent;
use App\Models\Enemies\PridefulEnemy;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property PridefulEnemy $model
 */
class PridefulEnemyChangedEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Model                       $context
     * @param User                        $user
     * @param PridefulEnemy|Model         $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected PridefulEnemy|Model                $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'pridefulenemy-changed';
    }

    public function broadcastWith(): array
    {
        return array_merge(
            parent::broadcastWith(),
            [
                'model_data' => $this->model->getCoordinatesData($this->coordinatesService),
            ],
        );
    }
}
