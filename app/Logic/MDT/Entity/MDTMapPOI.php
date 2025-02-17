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
    public const TYPE_DUNGEON_ENTRANCE     = 'dungeonEntrance';
    public const TYPE_GRAVEYARD            = 'graveyard';
    public const TYPE_GENERAL_NOTE         = 'generalNote';
    public const TYPE_ZOOM                 = 'zoom';
    public const TYPE_IRON_DOCKS_IRON_STAR = 'ironDocksIronStar';
    public const TYPE_NYALOTHA_SPIRE       = 'nyalothaSpire';
    public const TYPE_THE_UNDERROT_SKIP    = 'tuSkip';
    public const TYPE_BRACKENHIDE_CAGE     = 'brackenhideCage';
    public const TYPE_BRACKENHIDE_CAULDRON = 'brackenhideCauldron';
    public const TYPE_NELTHARUS_CHAIN      = 'neltharusChain';
    public const TYPE_NELTHARUS_FOOD       = 'neltharusFood';
    public const TYPE_NELTHARUS_SHIELD     = 'neltharusShield';
    public const TYPE_NW_ITEM              = 'nwItem';
    public const TYPE_ARA_KARA_ITEM        = 'araKaraItem';
    public const TYPE_MISTS_ITEM           = 'mistsItem';
    public const TYPE_STONEVAULT_ITEM      = 'stonevaultItem';
    public const TYPE_COT_ITEM             = 'cityOfThreadsItem';
    public const TYPE_CINDERBREW_ITEM_A    = 'brewItemA';
    public const TYPE_CINDERBREW_ITEM_B    = 'brewItemB';

    public const ALL_TYPES = [
        self::TYPE_MAP_LINK,
        self::TYPE_DUNGEON_ENTRANCE,
        self::TYPE_GRAVEYARD,
        self::TYPE_GENERAL_NOTE,
        self::TYPE_ZOOM,
        self::TYPE_IRON_DOCKS_IRON_STAR,
        self::TYPE_NYALOTHA_SPIRE,
        self::TYPE_THE_UNDERROT_SKIP,
        self::TYPE_BRACKENHIDE_CAGE,
        self::TYPE_BRACKENHIDE_CAULDRON,
        self::TYPE_NELTHARUS_CHAIN,
        self::TYPE_NELTHARUS_FOOD,
        self::TYPE_NELTHARUS_SHIELD,
        self::TYPE_NW_ITEM,
        self::TYPE_ARA_KARA_ITEM,
        self::TYPE_STONEVAULT_ITEM,
        self::TYPE_MISTS_ITEM,
        self::TYPE_COT_ITEM,
        self::TYPE_CINDERBREW_ITEM_A,
        self::TYPE_CINDERBREW_ITEM_B,
    ];

    private readonly string $template;
    private readonly string $type;
    private readonly ?int   $itemType;
    private readonly ?int   $itemIndex;
    private readonly ?int   $target;
    private readonly ?int   $direction;
    private readonly ?int   $connectionIndex;
    private readonly float  $x;
    private readonly float  $y;

    /**
     * @throws Exception
     */
    public function __construct(private readonly int $subLevel, private readonly array $rawMapPOI)
    {
        $this->template        = $this->rawMapPOI['template'];
        $this->type            = $this->rawMapPOI['type'];
        $this->itemType        = $this->rawMapPOI['itemType'] ?? null;
        $this->itemIndex       = $this->rawMapPOI['itemIndex'] ?? null;
        $this->target          = $this->rawMapPOI['target'] ?? null;
        $this->direction       = $this->rawMapPOI['direction'] ?? null;
        $this->connectionIndex = $this->rawMapPOI['connectionIndex'] ?? null;
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

    public function getItemType(): ?int
    {
        return $this->itemType;
    }

    public function getItemIndex(): ?int
    {
        return $this->itemIndex;
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
            'itemType'        => $this->itemType,
            'itemIndex'       => $this->itemIndex,
            'target'          => $this->target,
            'direction'       => $this->direction,
            'connectionIndex' => $this->connectionIndex,
            'x'               => $this->x,
            'y'               => $this->y,
        ];
    }
}
