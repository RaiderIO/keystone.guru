<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\WowheadPageRequest;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
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
        if (!config('app.debug', true)) {
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
        if (in_array($spell->id, Spell::BLOODLUSTY_SPELLS)) {
            unset($spellAttributes['category']);
        }

        $spell->update($spellAttributes);

        return json_encode($spellDataResult->toArray());
    }

    public function wowheadNpc(
        WowheadPageRequest      $request,
        WowheadServiceInterface $wowheadService,
    ): string {
        if (!config('app.debug', true)) {
            abort(StatusCode::FORBIDDEN);
        }

        $validated = $request->validated();

        preg_match('/npc=(\d+)/', (string)$validated['url'], $matches);

        $npcId = $matches[1] ?? null;

        $npc = Npc::findOrFail($npcId);

        $displayId = $wowheadService->getNpcDisplayId(
            GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
            $npc,
            $validated['html'],
        );

        $npc->update([
            'display_id' => $displayId,
        ]);

        return json_encode($npc->toArray());
    }
}
