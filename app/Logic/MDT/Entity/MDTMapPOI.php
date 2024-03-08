<?php

namespace App\Logic\MDT\Entity;

use Exception;
use Illuminate\Contracts\Support\Arrayable;

class MDTMapPOI implements Arrayable
{
    public const TEMPLATE_MAP_LINK_PIN = 'MapLinkPinTemplate';

    public const TEMPLATE_DEATH_RELEASE_PIN = 'DeathReleasePinTemplate';

    // Ny'alotha spires
    public const TEMPLATE_VIGNETTE_PIN = 'VignettePinTemplate';

    public const ALL_TEMPLATES = [
        self::TEMPLATE_MAP_LINK_PIN,
        self::TEMPLATE_DEATH_RELEASE_PIN,
        self::TEMPLATE_VIGNETTE_PIN,
    ];

    public const TYPE_MAP_LINK = 'mapLink';

    public const TYPE_GRAVEYARD = 'graveyard';

    public const TYPE_GENERAL_NOTE = 'generalNote';

    public const TYPE_ZOOM = 'zoom';

    public const TYPE_IRON_DOCKS_IRON_STAR = 'ironDocksIronStar';

    public const TYPE_NYALOTHA_SPIRE = 'nyalothaSpire';

    public const TYPE_THE_UNDERROT_SKIP = 'tuSkip';

    public const ALL_TYPES = [
        self::TYPE_MAP_LINK,
        self::TYPE_GRAVEYARD,
        self::TYPE_GENERAL_NOTE,
        self::TYPE_ZOOM,
        self::TYPE_IRON_DOCKS_IRON_STAR,
        self::TYPE_NYALOTHA_SPIRE,
        self::TYPE_THE_UNDERROT_SKIP,
    ];

    private readonly string $template;

    private readonly string $type;

    private readonly ?int $target;

    private readonly ?int $direction;

    private readonly ?int $connectionIndex;

    private readonly float $x;

    private readonly float $y;

    /**
     * @throws Exception
     */
    public function __construct(private readonly int $subLevel, private array $rawMapPOI)
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

    public function getSubLevel(): int
    {
        return $this->subLevel;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarget(): ?int
    {
        return $this->target;
    }

    public function getDirection(): ?int
    {
        return $this->direction;
    }

    public function getConnectionIndex(): ?int
    {
        return $this->connectionIndex;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function getRawMapPOI(): array
    {
        return $this->rawMapPOI;
    }

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
