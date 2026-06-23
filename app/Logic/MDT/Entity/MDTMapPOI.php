<?php

namespace App\Logic\MDT\Entity;

use Exception;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class MDTMapPOI implements Arrayable
{
    private readonly MDTMapPOITemplate $template;
    private readonly MDTMapPOIType $type;
    private readonly ?int $itemType;
    private readonly ?int $itemIndex;
    private readonly ?int $target;
    private readonly ?int $direction;
    private readonly ?int $connectionIndex;
    private readonly ?int $index;
    private readonly ?string $textAnchor;
    private readonly ?string $textAnchorTo;

    /** @var array<string, mixed>|null */
    private readonly ?array $info;

    private readonly ?float $sizeMult;
    private readonly float $x;
    private readonly float $y;

    /**
     * @param  array<string, mixed> $rawMapPOI
     * @throws Exception
     */
    public function __construct(private readonly int $subLevel, private readonly array $rawMapPOI)
    {
        $templateValue  = $this->rawMapPOI['template'] ?? MDTMapPOITemplate::LinkPin->value;
        $this->template = MDTMapPOITemplate::tryFrom($templateValue)
            ?? throw new Exception(sprintf('Found new template %s - we need to add it!', $templateValue));

        $typeValue  = $this->rawMapPOI['type'];
        $this->type = MDTMapPOIType::tryFrom($typeValue)
            ?? throw new Exception(sprintf('Found new type %s - we need to add it!', $typeValue));

        $this->itemType        = $this->rawMapPOI['itemType'] ?? null;
        $this->itemIndex       = $this->rawMapPOI['itemIndex'] ?? null;
        $this->target          = $this->rawMapPOI['target'] ?? null;
        $this->direction       = $this->rawMapPOI['direction'] ?? null;
        $this->connectionIndex = $this->rawMapPOI['connectionIndex'] ?? null;
        $this->index           = $this->rawMapPOI['index'] ?? null;
        $this->textAnchor      = $this->rawMapPOI['textAnchor'] ?? null;
        $this->textAnchorTo    = $this->rawMapPOI['textAnchorTo'] ?? null;
        $this->info            = $this->rawMapPOI['info'] ?? null;
        $this->sizeMult        = $this->rawMapPOI['sizeMult'] ?? null;
        $this->x               = $this->rawMapPOI['x'];
        $this->y               = $this->rawMapPOI['y'];
    }

    public function getSubLevel(): int
    {
        return $this->subLevel;
    }

    public function getTemplate(): MDTMapPOITemplate
    {
        return $this->template;
    }

    public function getType(): MDTMapPOIType
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

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function getTextAnchor(): ?string
    {
        return $this->textAnchor;
    }

    public function getTextAnchorTo(): ?string
    {
        return $this->textAnchorTo;
    }

    /** @return array<string, mixed>|null */
    public function getInfo(): ?array
    {
        return $this->info;
    }

    public function getSubType(): ?string
    {
        return $this->info['atlas'] ?? null;
    }

    public function getSizeMult(): ?float
    {
        return $this->sizeMult;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawMapPOI(): array
    {
        return $this->rawMapPOI;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subLevel'        => $this->subLevel,
            'template'        => $this->template->value,
            'type'            => $this->type->value,
            'itemType'        => $this->itemType,
            'itemIndex'       => $this->itemIndex,
            'target'          => $this->target,
            'direction'       => $this->direction,
            'connectionIndex' => $this->connectionIndex,
            'index'           => $this->index,
            'textAnchor'      => $this->textAnchor,
            'textAnchorTo'    => $this->textAnchorTo,
            'info'            => $this->info,
            'sizeMult'        => $this->sizeMult,
            'x'               => $this->x,
            'y'               => $this->y,
        ];
    }
}
