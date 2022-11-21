<?php

namespace App\Logic\MDT\Entity;

use Illuminate\Contracts\Support\Arrayable;

class MDTMapPOI implements Arrayable
{
    public const TEMPLATE_MAP_LINK_PIN      = 'MapLinkPinTemplate';
    public const TEMPLATE_DEATH_RELEASE_PIN = 'DeathReleasePinTemplate';

    public const ALL_TEMPLATES = [
        self::TEMPLATE_MAP_LINK_PIN,
        self::TEMPLATE_DEATH_RELEASE_PIN,
    ];

    public const TYPE_MAP_LINK             = 'mapLink';
    public const TYPE_GRAVEYARD            = 'graveyard';
    public const TYPE_GENERAL_NOTE         = 'generalNote';
    public const TYPE_ZOOM                 = 'zoom';
    public const TYPE_IRON_DOCKS_IRON_STAR = 'ironDocksIronStar';

    public const ALL_TYPES = [
        self::TYPE_MAP_LINK,
        self::TYPE_GRAVEYARD,
        self::TYPE_GENERAL_NOTE,
        self::TYPE_ZOOM,
        self::TYPE_IRON_DOCKS_IRON_STAR,
    ];

    private int $subLevel;

    private string $template;

    private string $type;

    private int $target;

    private int $direction;

    private int $connectionIndex;

    private float $x;

    private float $y;

    private array $rawMapPOI;

    /**
     * @param int $subLevel
     * @param array $rawMapPOI
     * @throws \Exception
     */
    public function __construct(int $subLevel, array $rawMapPOI)
    {
        $this->subLevel  = $subLevel;
        $this->rawMapPOI = $rawMapPOI;

        $this->template        = $rawMapPOI['template'];
        $this->type            = $rawMapPOI['type'];
        $this->target          = $rawMapPOI['target'];
        $this->direction       = $rawMapPOI['direction'];
        $this->connectionIndex = $rawMapPOI['connectionIndex'];
        $this->x               = $rawMapPOI['x'];
        $this->y               = $rawMapPOI['y'];

        if (!in_array($this->template, self::ALL_TEMPLATES)) {
            throw new \Exception(sprintf('Found new template %s - we need to add it!', $this->template));
        }

        if (!in_array($this->type, self::ALL_TYPES)) {
            throw new \Exception(sprintf('Found new type %s - we need to add it!', $this->type));
        }
    }

    /**
     * @return int
     */
    public function getSubLevel(): int
    {
        return $this->subLevel;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getTarget(): int
    {
        return $this->target;
    }

    /**
     * @return int
     */
    public function getDirection(): int
    {
        return $this->direction;
    }

    /**
     * @return int
     */
    public function getConnectionIndex(): int
    {
        return $this->connectionIndex;
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return array
     */
    public function getRawMapPOI(): array
    {
        return $this->rawMapPOI;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'subLevel'        => $this->subLevel,
            'template'        => $this->template,
            'type'            => $this->type,
            'target'          => $this->target,
            'direction'       => $this->direction,
            'connectionIndex' => $this->connectionIndex,
            'x'               => $this->x,
            'y'               => $this->y,
        ];
    }
}
