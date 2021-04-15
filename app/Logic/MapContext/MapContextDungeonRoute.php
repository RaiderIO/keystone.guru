<?php


namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\Floor;

/**
 * Class MapContextDungeonRoute
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 06/08/2020
 *
 * @property DungeonRoute $_context
 */
class MapContextDungeonRoute extends MapContext
{
    use PublicKeyDungeonRoute;

    public function __construct(DungeonRoute $dungeonRoute, Floor $floor)
    {
        parent::__construct($dungeonRoute, $floor);
    }

    public function getType(): string
    {
        return 'dungeonroute';
    }

    public function isTeeming(): bool
    {
        return $this->_context->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->_context->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->_context->dungeon->id, false, $this->_context);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'publicKey'               => $this->_context->public_key,
            'teamId'                  => $this->_context->team_id,
            'pullGradient'            => $this->_context->pull_gradient,
            'pullGradientApplyAlways' => $this->_context->pull_gradient_apply_always,
            'faction'                 => strtolower($this->_context->faction->name),
            'enemyForces'             => $this->_context->enemy_forces,

            // Relations
            'killZones'               => $this->_context->killzones,
            'mapIcons'                => $this->_context->mapicons,
            'paths'                   => $this->_context->paths,
            'brushlines'              => $this->_context->brushlines,
            'pridefulenemies'         => $this->_context->pridefulenemies,
        ]);
    }


}