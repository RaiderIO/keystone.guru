<?php


namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Model;

abstract class MapContext
{
    use ListsEnemies;

    /** @var Model */
    protected Model $_context;

    /**
     * @var Floor
     */
    private Floor $_floor;

    function __construct(Model $context, Floor $floor)
    {
        $this->_context = $context;
        $this->_floor = $floor;
    }

    /**
     * @return string
     */
    public abstract function getType(): string;

    /**
     * @return bool
     */
    public abstract function isTeeming(): bool;

    /**
     * @return int
     */
    public abstract function getSeasonalIndex(): int;

    /**
     * @return array
     */
    public abstract function getEnemies(): array;

    /**
     * @return array
     */
    public function toArray(): array
    {
        $dungeon = $this->_floor->dungeon->load(['enemies', 'enemypacks','enemypatrols', 'mapicons']);
        return [
            'type'          => $this->getType(),
            'floorId'       => $this->_floor->id,
            'teeming'       => $this->isTeeming(),
            'seasonalIndex' => $this->getSeasonalIndex(),
            'dungeon'       => array_merge($this->_floor->dungeon->toArray(), $this->getEnemies(), [
                'enemies' => $dungeon->enemies,
                'enemyPacks' => $dungeon->enemypacks,
                'enemyPatrols' => $dungeon->enemypatrols,
                'mapIcons' => $dungeon->mapicons,
            ]),
            // @TODO Probably move this? Temp fix
            'npcsMinHealth' => $this->_floor->dungeon->getNpcsMinHealth(),
            'npcsMaxHealth' => $this->_floor->dungeon->getNpcsMaxHealth()
        ];
    }
}