<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Models\CharacterClass;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CompendiumController extends Controller
{
    /**
     * @return array{npc: int, spell: int, class: int}
     */
    private function getStats(): array
    {
        return Cache::remember('compendium.index.stats', now()->addHour(), static fn(): array => [
            'npc'   => Npc::count(),
            'spell' => Spell::count(),
            'class' => CharacterClass::count(),
        ]);
    }

    public function index(): View
    {
        return view('compendium.index', [
            'stats' => $this->getStats(),
        ]);
    }
}
