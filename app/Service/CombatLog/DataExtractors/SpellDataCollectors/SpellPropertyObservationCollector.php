<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedInterface;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell as SpellModel;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SpellPropertyObservationCollector implements SpellDataCollectorInterface
{
    /**
     * All (spell_id, property) pairs observed this session — batch-upserted in afterCollect.
     *
     * @var Collection<string, array{spell_id: int, property: SpellProperty}>
     */
    private Collection $pendingPropertyObservations;

    private ?string $currentCombatLogFilePath = null;

    /**
     * @param Collection<int, SpellModel> $allSpells
     */
    public function __construct(
        private readonly Collection $allSpells,
    ) {
        $this->pendingPropertyObservations = collect();
    }

    public function beforeCollect(string $combatLogFilePath): void
    {
        $this->currentCombatLogFilePath = $combatLogFilePath;
    }

    public function collectFromEvent(CombatLogEvent $parsedEvent, Spell $prefix): void
    {
        $spellId  = $prefix->getSpellId();
        $suffix   = $parsedEvent->getSuffix();
        $destGuid = $parsedEvent->getGenericData()->getDestGuid();

        $properties = [];

        if ($suffix instanceof AuraAppliedInterface) {
            if ($suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF &&
                $destGuid instanceof Creature &&
                $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE) {
                $properties[] = SpellProperty::Aura;
            } elseif ($suffix->getAuraType() === AuraBase::AURA_TYPE_DEBUFF && $destGuid instanceof Player) {
                $properties[] = SpellProperty::Debuff;
            }
        }

        if ($suffix instanceof MissedInterface) {
            $bit = SpellModel::GUID_MISS_TYPE_MAPPING[$suffix->getMissType()::class] ?? null;
            if ($bit !== null) {
                $properties[] = SpellProperty::fromMissTypeBit($bit);
            }
        }

        foreach ($properties as $property) {
            $this->queueObservation($spellId, $property);
        }
    }

    public function collectInterrupt(int $spellId): void
    {
        $this->queueObservation($spellId, SpellProperty::MissInterrupt);
    }

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        if ($this->pendingPropertyObservations->isNotEmpty()) {
            $now  = Carbon::now()->toDateTimeString();
            $rows = $this->pendingPropertyObservations->map(fn(array $observation) => [
                'spell_id'        => $observation['spell_id'],
                'property'        => $observation['property']->value,
                'observed_on'     => Carbon::today()->toDateString(),
                'combat_log_path' => $this->currentCombatLogFilePath ?? '',
                'created_at'      => $now,
                'updated_at'      => $now,
            ])->all();

            CombatLogSpellPropertyObservation::upsert(
                $rows,
                ['spell_id', 'property', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );

            foreach ($this->pendingPropertyObservations as $observation) {
                /** @var SpellModel|null $spell */
                $spell = $this->allSpells->get($observation['spell_id']);
                if ($spell === null || $this->spellHasProperty($spell, $observation['property'])) {
                    continue;
                }

                $this->applyPropertyToSpell($spell, $observation['property']);

                if ($spell->isDirty() && $spell->save()) {
                    $result->updatedSpell();

                    CombatLogSpellEvent::create([
                        'spell_id'        => $observation['spell_id'],
                        'event_type'      => CombatLogSpellEventType::PropertyChanged,
                        'property'        => $observation['property'],
                        'combat_log_path' => $this->currentCombatLogFilePath,
                    ]);
                }
            }
        }

        $this->pendingPropertyObservations = collect();
        $this->currentCombatLogFilePath    = null;
    }

    private function queueObservation(int $spellId, SpellProperty $property): void
    {
        $dedupKey = sprintf('%d-%s', $spellId, $property->value);
        if (!$this->pendingPropertyObservations->has($dedupKey)) {
            $this->pendingPropertyObservations->put($dedupKey, [
                'spell_id' => $spellId,
                'property' => $property,
            ]);
        }
    }

    private function applyPropertyToSpell(SpellModel $spell, SpellProperty $property): void
    {
        if ($property === SpellProperty::Aura) {
            $spell->aura = true;
        } elseif ($property === SpellProperty::Debuff) {
            $spell->debuff = true;
        } else {
            $spell->miss_types_mask |= $this->missTypeBit($property);
        }
    }

    private function spellHasProperty(SpellModel $spell, SpellProperty $property): bool
    {
        if ($property === SpellProperty::Aura) {
            return $spell->aura;
        }

        if ($property === SpellProperty::Debuff) {
            return (bool)$spell->debuff;
        }

        return (bool)($spell->miss_types_mask & $this->missTypeBit($property));
    }

    private function missTypeBit(SpellProperty $property): int
    {
        $name = substr($property->value, 5);
        $bit  = array_search($name, SpellModel::ALL_MISS_TYPES, true);

        return $bit !== false ? (int)$bit : 0;
    }
}
