<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Contracts\Support\Arrayable;

class CreateRouteSettings implements Arrayable
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

    public static function createFromArray(array $body): CreateRouteSettings
    {
        return new CreateRouteSettings(
            $body['temporary'] ?? true,
            $body['debugIcons'] ?? false,
        );
    }
}
