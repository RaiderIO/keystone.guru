<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteSettings
{

    public function __construct(public bool $temporary, public bool $debugIcons)
    {
    }

    /**
     * @return CreateRouteSettings
     */
    public static function createFromArray(array $body): CreateRouteSettings
    {
        return new CreateRouteSettings(
            $body['temporary'] ?? true,
            $body['debugIcons'] ?? false,
        );
    }
}
