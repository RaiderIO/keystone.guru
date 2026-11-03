<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\WowheadPageRequest;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClass;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcHealth;
use App\Models\Spell\KnownSpell;
use App\Models\Spell\Spell;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode;

class WowheadWebhookController extends Controller
{
    public function wowheadOptions(Request $request): Response
    {
        return response()->noContent();
    }

    public function wowheadSpell(
        WowheadPageRequest      $request,
        WowheadServiceInterface $wowheadService,
    ): string {
        if (!config('app.debug', false)) {
            abort(StatusCode::FORBIDDEN);
        }

        $validated = $request->validated();

        preg_match('/spell=(\d+)/', (string)$validated['url'], $matches);

        $spellId = $matches[1] ?? null;

        $retail = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        $spell = Spell::find($spellId) ?? Spell::create([
            'id'              => $spellId,
            'game_version_id' => $retail->id,
        ]);

        $spellDataResult = $wowheadService->getSpellData(
            $gameVersion = $retail,
            (int)$spellId,
            $validated['html'],
        );

        $spellAttributes                    = $spellDataResult->toArray();
        $spellAttributes['game_version_id'] = $gameVersion->id;
        $spellAttributes['fetched_data_at'] = Carbon::now();

        // Prevent category updates when we change it manually
        if (in_array($spell->id, KnownSpell::BLOODLUSTY_SPELLS)) {
            unset($spellAttributes['category']);
        }

        $spell->update($spellAttributes);

        return json_encode($spellDataResult->toArray());
    }

    public function wowheadNpc(
        WowheadPageRequest      $request,
        WowheadServiceInterface $wowheadService,
    ): string {
        if (!config('app.debug', false)) {
            abort(StatusCode::FORBIDDEN);
        }

        $validated = $request->validated();

        preg_match('/npc=(\d+)/', (string)$validated['url'], $matches);

        $npcId = $matches[1] ?? null;

        $retail = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        $npc = Npc::find($npcId);
        if ($npc === null) {
            $npc = $this->createNpcFromWowheadPage($wowheadService, $retail, (int)$npcId, $validated['html']);
        } else {
            $npc->update([
                'display_id' => $wowheadService->getNpcDisplayId($retail, $npc, $validated['html']),
            ]);
        }

        return json_encode($npc->toArray());
    }

    private function createNpcFromWowheadPage(
        WowheadServiceInterface $wowheadService,
        GameVersion             $gameVersion,
        int                     $npcId,
        string                  $html,
    ): Npc {
        $npcData = $wowheadService->getNpcData($npcId, $html);
        if ($npcData === null) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, sprintf('Unable to find data for npc %d on the page', $npcId));
        }

        return DB::transaction(function () use ($npcData, $gameVersion): Npc {
            $npc = Npc::query()->create([
                'id'                => $npcData->getNpcId(),
                'game_version_id'   => $gameVersion->id,
                'classification_id' => NpcClassification::ALL[$npcData->getClassificationKey()],
                'npc_type_id'       => $npcData->getNpcTypeId(),
                'npc_class_id'      => NpcClass::ALL[NpcClass::NPC_CLASS_MELEE],
                'display_id'        => $npcData->getDisplayId(),
                'name'              => $npcData->getName(),
                'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            ]);

            $dungeonIds = Dungeon::query()
                ->whereIn('zone_id', $npcData->getZoneIds())
                ->pluck('id');

            foreach ($dungeonIds as $dungeonId) {
                NpcDungeon::query()->create([
                    'npc_id'     => $npc->id,
                    'dungeon_id' => $dungeonId,
                ]);
            }

            NpcHealth::query()->create([
                'npc_id'          => $npc->id,
                'game_version_id' => $gameVersion->id,
                'health'          => NpcHealth::HEALTH_PLACEHOLDER,
                'percentage'      => null,
            ]);

            return $npc;
        });
    }
}
