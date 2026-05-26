<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminToolsSpellsController extends Controller
{
    public function spellsShowMissingSpellInfo(
        SpellRepositoryInterface $spellRepository,
    ): View {
        $missingSpells = $spellRepository->getMissingSpellIds();

        return view('admin.tools.spells.showmissingspellinfo', [
            'spells' => Spell::whereNull('fetched_data_at')->get()->merge(
                collect($missingSpells)->map(fn($spellId) => new Spell(['id' => $spellId, 'name' => 'Unknown'])),
            ),
        ]);
    }

    public function spellsSaveToSeeder(): Response
    {
        $spells = Spell::with('spellDungeons')->get();
        foreach ($spells as $spell) {
            $spell->makeHidden(['icon_url', 'wowhead_url'])->makeVisible(['spellDungeons']);
        }

        return response(json_encode($spells->toArray(), JSON_PRETTY_PRINT), 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="spells.json"',
        ]);
    }
}
