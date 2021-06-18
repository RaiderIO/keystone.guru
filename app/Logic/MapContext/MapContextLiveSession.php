<?php


namespace App\Logic\MapContext;

use App\Models\Floor;
use App\Models\LiveSession;

/**
 * Class MapContextLiveSession
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 13/05/2021
 *
 * @property LiveSession $_context
 */
class MapContextLiveSession extends MapContext
{
    /** @var LiveSession */
    private LiveSession $liveSession;

    use DungeonRouteTrait;

    public function __construct(LiveSession $liveSession, Floor $floor)
    {
        parent::__construct($liveSession, $floor);

        $this->liveSession = $liveSession;
    }

    public function getType(): string
    {
        return 'livesession';
    }

    public function isTeeming(): bool
    {
        return $this->_context->dungeonroute->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->_context->dungeonroute->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->_context->dungeonroute->dungeon->id, false, $this->_context->dungeonroute);
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-live-session.%s', env('APP_TYPE'), $this->_context->getRouteKey());
    }

    public function getProperties(): array
    {
        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties($this->_context->dungeonroute), [
            'liveSessionPublicKey' => $this->_context->public_key,
            'expiresInSeconds'   => $this->_context->getExpiresInSeconds()
        ]);
    }

}