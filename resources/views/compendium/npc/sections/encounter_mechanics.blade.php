<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Service\Compendium\Dtos\NpcEncounterMechanics;

/**
 * The section title + tooltip live in the parent record section's label rail (see show.blade.php). Only included
 * when there is something to show.
 *
 * @var Npc                   $npc
 * @var NpcEncounterMechanics $encounterMechanics
 */

$translationKey = 'view_compendium.npc.sections.encounter_mechanics';

$npcLink = static fn(?Npc $linkedNpc, ?int $npcId = null): string => $linkedNpc === null
    ? e((string)$npcId)
    : sprintf('<a href="%s">%s</a>', e(route('npc.compendium.show', $linkedNpc)), e(__($linkedNpc->name)));

$spellLink = static fn(NpcEncounterMechanic $mechanic): string => $mechanic->spell === null
    ? e((string)$mechanic->spell_id)
    : trim(view('common.spell.link', ['spell' => $mechanic->spell, 'size' => 20])->render());

$timing = static fn(NpcEncounterMechanic $mechanic): array => [
    'first'    => NpcEncounterMechanic::formatTime($mechanic->first_ms ?? 0),
    'cooldown' => NpcEncounterMechanic::formatTime($mechanic->cooldown_ms ?? 0),
    'duration' => (int)round(($mechanic->duration_ms ?? 0) / 1000),
];

$encounterLine = static function (NpcEncounterMechanic $mechanic) use ($npc, $encounterMechanics, $translationKey, $npcLink, $spellLink, $timing): ?string {
    switch ($mechanic->type) {
        case NpcEncounterMechanicType::Adds:
            $replace = array_merge($timing($mechanic), ['count' => $mechanic->count ?? 1, 'npc' => $npcLink($mechanic->npc, $mechanic->npc_id)]);
            if ($mechanic->first_health_pct !== null) {
                return __(sprintf('%s.adds_health', $translationKey), array_merge($replace, ['health' => round($mechanic->first_health_pct)]));
            }

            return __(sprintf('%s.%s', $translationKey, $mechanic->cooldown_ms === null ? 'adds_once' : 'adds'), $replace);
        case NpcEncounterMechanicType::Invulnerable:
            return __(
                sprintf('%s.%s', $translationKey, $mechanic->cooldown_ms === null ? 'invulnerable_once' : 'invulnerable'),
                array_merge($timing($mechanic), ['spell' => $spellLink($mechanic)]),
            );
        case NpcEncounterMechanicType::Movement:
            return __(
                sprintf('%s.%s', $translationKey, $mechanic->cooldown_ms === null ? 'movement_once' : 'movement'),
                array_merge($timing($mechanic), ['spell' => $spellLink($mechanic)]),
            );
        case NpcEncounterMechanicType::SharedHealth:
            $sharedHealthNpcIds = $mechanic->getSharedHealthNpcIds();
            if (count($sharedHealthNpcIds) === 1) {
                return __(sprintf('%s.shared_health_same', $translationKey), [
                    'count' => $mechanic->count,
                    'npc'   => $npcLink($encounterMechanics->npcsById->get($sharedHealthNpcIds[0]), $sharedHealthNpcIds[0]),
                ]);
            }

            $otherNpcIds = array_values(array_diff($sharedHealthNpcIds, [$npc->id]));
            if ($otherNpcIds === []) {
                return null;
            }

            $links = array_map(static fn(int $npcId) => $npcLink($encounterMechanics->npcsById->get($npcId), $npcId), $otherNpcIds);
            $last  = array_pop($links);

            return __(sprintf('%s.shared_health', $translationKey), [
                'npcs' => $links === [] ? $last : __(sprintf('%s.npc_list', $translationKey), ['npcs' => implode(', ', $links), 'last' => $last]),
            ]);
        case NpcEncounterMechanicType::Friendly:
            return __(sprintf('%s.friendly', $translationKey), ['npc' => $npcLink($mechanic->npc, $mechanic->npc_id)]);
        case NpcEncounterMechanicType::AffixExempt:
            return __(sprintf('%s.affix_exempt', $translationKey), ['npc' => $npcLink($mechanic->npc, $mechanic->npc_id)]);
        default:
            return null;
    }
};

$npcLine = static function (NpcEncounterMechanic $mechanic) use ($encounterMechanics, $translationKey, $npcLink): ?string {
    $bossNpc = $encounterMechanics->getBossNpc($mechanic);

    return match ($mechanic->type) {
        NpcEncounterMechanicType::Adds        => __(sprintf('%s.npc_adds', $translationKey), ['boss' => $npcLink($bossNpc, $mechanic->encounter_id)]),
        NpcEncounterMechanicType::Friendly    => __(sprintf('%s.npc_friendly', $translationKey), ['boss' => $npcLink($bossNpc, $mechanic->encounter_id)]),
        NpcEncounterMechanicType::AffixExempt => __(sprintf('%s.npc_affix_exempt', $translationKey)),
        default                               => null,
    };
};

$lines = [];
foreach ($encounterMechanics->encounterMechanics as $mechanic) {
    $lines[] = ['html' => $encounterLine($mechanic), 'mechanic' => $mechanic];
}

foreach ($encounterMechanics->npcMechanics as $mechanic) {
    $lines[] = ['html' => $npcLine($mechanic), 'mechanic' => $mechanic];
}

$lines = array_filter($lines, static fn(array $line) => $line['html'] !== null);
?>
@if($lines !== [])
    <ul class="mb-0">
        @foreach($lines as $line)
            <li>
                {!! $line['html'] !!}
                <span class="text-muted">({{ __(sprintf('%s.evidence', $translationKey), ['count' => $line['mechanic']->run_count, 'total' => $line['mechanic']->total_run_count]) }})</span>
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted mb-0">{{ __(sprintf('%s.empty', $translationKey)) }}</p>
@endif
