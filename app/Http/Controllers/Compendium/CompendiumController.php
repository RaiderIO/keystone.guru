<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Models\CharacterClass;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellTuningChange;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CompendiumController extends Controller
{
    /**
     * @return array{npc: int, spell: int, class: int, tuning_builds: int}
     */
    private function getStats(): array
    {
        return Cache::remember('compendium.index.stats', now()->addHour(), static fn(): array => [
            'npc'           => Npc::count(),
            'spell'         => Spell::count(),
            'class'         => CharacterClass::count(),
            'tuning_builds' => SpellTuningChange::query()->distinct()->count('to_build'),
        ]);
    }

    public function index(): View
    {
        return view('compendium.index', [
            'stats' => $this->getStats(),
        ]);
    }
}
