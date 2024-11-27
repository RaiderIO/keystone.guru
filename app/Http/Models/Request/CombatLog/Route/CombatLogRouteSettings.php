<?php

namespace App\Http\Models\Request\CombatLog\Route;

use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteSettings implements Arrayable
{
    public function __construct(public bool $temporary, public bool $debugIcons)
    {
    }

    public function toArray(): array
    {
        return [
            'temporary'  => $this->temporary,
            'debugIcons' => $this->debugIcons,
        ];
    }

    public static function createFromArray(array $body): CombatLogRouteSettings
    {
        return new CombatLogRouteSettings(
            $body['temporary'] ?? true,
            $body['debugIcons'] ?? false,
        );
    }
}
