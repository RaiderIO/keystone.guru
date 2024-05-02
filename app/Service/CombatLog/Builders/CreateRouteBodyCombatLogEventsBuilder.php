<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Repositories\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\KillZone\KillZoneRepositoryInterface;
use App\Repositories\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Logging\CreateRouteBodyCombatLogEventsBuilderLoggingInterface;
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CreateRouteBodyCombatLogEventsBuilder extends CreateRouteBodyDungeonRouteBuilder
{
    public function __construct(
        SeasonServiceInterface           $seasonService,
        CoordinatesServiceInterface      $coordinatesService,
        DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        KillZoneRepositoryInterface      $killZoneRepository,
        KillZoneEnemyRepositoryInterface $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface $killZoneSpellRepository,
        CreateRouteBody                  $createRouteBody
    ) {
        parent::__construct(
            $seasonService,
            $coordinatesService,
            $dungeonRouteRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $createRouteBody
        );

        /** @var CreateRouteBodyCombatLogEventsBuilderLoggingInterface $log */
        $log       = App::make(CreateRouteBodyCombatLogEventsBuilderLoggingInterface::class);
        $this->log = $log;
    }

    public function getCombatLogEvents(): Collection
    {
        try {
            $this->log->getCombatLogEventsStart();


        } finally {
            $this->log->getCombatLogEventsEnd();

        }
    }
}
