<?php

namespace App\Dto\Request\Route;

use App\Dto\Request\RequestDto;
use Illuminate\Support\Str;
use Override;

/**
 * @OA\Schema(schema="RouteThumbnailRequest")
 */
class DungeonRouteThumbnailRequestDto extends RequestDto
{
    public function __construct()
    {
    }

    /**
     * @OA\Property(property="viewport_width",minimum="192",maximum="1620",example="900")
     */
    public ?int $viewportWidth = null;

    /**
     * @OA\Property(property="viewport_height",minimum="128",maximum="1080",example="600")
     */
    public ?int $viewportHeight = null;

    /**
     * @OA\Property(property="image_width",minimum="192",maximum="1620",example="900")
     */
    public ?int $imageWidth = null;

    /**
     * @OA\Property(property="image_height",minimum="128",maximum="1080",example="600")
     */
    public ?int $imageHeight = null;

    /**
     * @OA\Property(property="zoom_level",minimum="1",maximum="5",example="2.2")
     */
    public ?float $zoomLevel = null;

    /**
     * @OA\Property(property="quality",minimum="1",maximum="100",example="90")
     */
    public ?int $quality = null;

    /**
     * @param array<string, mixed> $data the request's snake_case keys, as validated
     */
    #[Override]
    public static function createFromArray(array $data): static
    {
        return parent::createFromArray(
            collect($data)->mapWithKeys(static fn(mixed $value, string $key) => [Str::camel($key) => $value])->all(),
        );
    }
}
