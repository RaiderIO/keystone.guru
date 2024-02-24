<?php

namespace App\Logic\MDT\Entity;

use Exception;
use Illuminate\Contracts\Support\Arrayable;

class MDTMapPOI implements Arrayable
{
    public const TEMPLATE_MAP_LINK_PIN      = 'MapLinkPinTemplate';
    public const TEMPLATE_DEATH_RELEASE_PIN = 'DeathReleasePinTemplate';
    // Ny'alotha spires
    public const TEMPLATE_VIGNETTE_PIN = 'VignettePinTemplate';

    public const ALL_TEMPLATES = [
        self::TEMPLATE_MAP_LINK_PIN,
        self::TEMPLATE_DEATH_RELEASE_PIN,
        self::TEMPLATE_VIGNETTE_PIN,
    ];

    public const TYPE_MAP_LINK             = 'mapLink';
    public const TYPE_GRAVEYARD            = 'graveyard';
    public const TYPE_GENERAL_NOTE         = 'generalNote';
    public const TYPE_ZOOM                 = 'zoom';
    public const TYPE_IRON_DOCKS_IRON_STAR = 'ironDocksIronStar';
    public const TYPE_NYALOTHA_SPIRE       = 'nyalothaSpire';
    public const TYPE_THE_UNDERROT_SKIP    = 'tuSkip';

    public const ALL_TYPES = [
        self::TYPE_MAP_LINK,
        self::TYPE_GRAVEYARD,
        self::TYPE_GENERAL_NOTE,
        self::TYPE_ZOOM,
        self::TYPE_IRON_DOCKS_IRON_STAR,
        self::TYPE_NYALOTHA_SPIRE,
        self::TYPE_THE_UNDERROT_SKIP,
    ];

    private string $template;

    private string $type;

    private ?int $target;

    private ?int $direction;

    private ?int $connectionIndex;

    private float $x;

    private float $y;

    /**
     * @throws Exception
     */
    public function __construct(private int $subLevel, private array $rawMapPOI)
    {
        $this->template        = $this->rawMapPOI['template'];
        $this->type            = $this->rawMapPOI['type'];
        $this->target          = $this->rawMapPOI['target'] ?? 0;
        $this->direction       = $this->rawMapPOI['direction'] ?? 0;
        $this->connectionIndex = $this->rawMapPOI['connectionIndex'] ?? 0;
        $this->x               = $this->rawMapPOI['x'];
        $this->y               = $this->rawMapPOI['y'];

        if (!in_array($this->template, self::ALL_TEMPLATES)) {
            throw new Exception(sprintf('Found new template %s - we need to add it!', $this->template));
        }

        if (!in_array($this->type, self::ALL_TYPES)) {
            throw new Exception(sprintf('Found new type %s - we need to add it!', $this->type));
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
     * @return int|null
     */
    public function getTarget(): ?int
    {
        return $this->target;
    }

    /**
     * @return int|null
     */
    public function getDirection(): ?int
    {
        return $this->direction;
    }

    /**
     * @return int|null
     */
    public function getConnectionIndex(): ?int
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
