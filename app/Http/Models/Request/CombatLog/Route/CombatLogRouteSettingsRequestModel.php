<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteSettings")
 * @OA\Property(property="temporary", type="boolean", nullable=true)
 * @OA\Property(property="debugIcons", type="boolean", nullable=true)
 * @OA\Property(property="mappingVersion", type="integer", nullable=true)
 */
class CombatLogRouteSettingsRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?bool $temporary = null,
        public ?bool $debugIcons = null,
        public ?int  $mappingVersion = null,
    ) {
    }

    #[\Override]
    public function toArray(): array
    {
        // Make sure that mappingVersion is not echoed if it is null
        // This is important for backwards compatibility
        return array_filter([
            'temporary'      => $this->temporary,
            'debugIcons'     => $this->debugIcons,
            'mappingVersion' => $this->mappingVersion,
        ], fn($value) => !is_null($value));
    }
}
