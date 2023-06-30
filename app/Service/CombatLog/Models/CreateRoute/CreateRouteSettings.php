<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteSettings
{

    public bool $temporary = true;

    public bool $debugIcons = false;
    
    /**
     * @param bool $temporary
     * @param bool $debugIcons
     */
    public function __construct(bool $temporary, bool $debugIcons)
    {
        $this->temporary  = $temporary;
        $this->debugIcons = $debugIcons;
    }

    /**
     * @param array $body
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
