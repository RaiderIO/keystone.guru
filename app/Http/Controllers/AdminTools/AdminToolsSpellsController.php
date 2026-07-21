<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\Mapping\MappingExportServiceInterface;
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

    public function spellsSaveToSeeder(MappingExportServiceInterface $mappingExportService): Response
    {
        return response(json_encode($mappingExportService->serializeSpells(), JSON_PRETTY_PRINT), 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="spells.json"',
        ]);
    }
}
