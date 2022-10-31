<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Speedrun\DungeonSpeedrunRequiredNpcsFormRequest;
use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\Npc\NpcServiceInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Session;

class MappingVersionController extends Controller
{
    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param MappingServiceInterface $mappingService
     * @return RedirectResponse
     */
    public function savenew(Request $request, Dungeon $dungeon, MappingServiceInterface $mappingService)
    {
        $mappingService->createNewMappingVersion($dungeon);

        Session::flash('status', 'Added new mapping version!');

        return redirect()->route('admin.dungeon.edit', [
            'dungeon' => $dungeon,
        ]);
    }
}
