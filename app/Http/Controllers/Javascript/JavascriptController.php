<?php

namespace App\Http\Controllers\Javascript;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\MapContext\MapContextServiceInterface;
use Debugbar;
use Illuminate\Http\Response;
use Psr\SimpleCache\InvalidArgumentException;

class JavascriptController
{
    use RemembersToFile;

    public function __construct()
    {
        // Disable Debugbar for all actions in this controller
        if (class_exists(Debugbar::class)) {
            Debugbar::disable();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mapContextMappingVersionData(
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        MappingVersion             $mappingVersion,
        string                     $mapFacadeStyle,
    ): Response {
        $mapContextMappingVersionData = $mapContextService->createMapContextMappingVersionData(
            $dungeon,
            $mappingVersion,
            $mapFacadeStyle,
        );

        $content = sprintf(
            'let mapContextMappingVersionData = %s;',
            json_encode($mapContextMappingVersionData->toArray(), JSON_PRETTY_PRINT)
        );

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mapContextDungeonData(
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        string                     $locale,
    ): Response {
        $mapContextDungeonData = $mapContextService->createMapContextDungeonData(
            $dungeon,
            $locale,
        );

        $content = sprintf(
            'let mapContextDungeonData = %s;',
            json_encode($mapContextDungeonData->toArray(), JSON_PRETTY_PRINT)
        );

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mapContextStaticData(
        MapContextServiceInterface $mapContextService,
        string                     $locale,
    ): Response {
        $mapContextStaticData = $mapContextService->createMapContextStaticData(
            $locale,
        );

        $content = sprintf(
            'let mapContextStaticData = %s;',
            json_encode($mapContextStaticData->toArray(), JSON_PRETTY_PRINT)
        );

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8');
    }
}
