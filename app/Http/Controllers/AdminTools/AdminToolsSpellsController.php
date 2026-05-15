<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;
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
}
