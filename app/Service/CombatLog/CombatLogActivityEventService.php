<?php

namespace App\Service\CombatLog;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\SpellProperty;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcCharacteristicObservationRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellPropertyObservationRepositoryInterface;
use App\Service\CombatLog\Dtos\ObservationDensity;
use App\Service\CombatLog\Logging\CombatLogActivityEventServiceLoggingInterface;
use Illuminate\Support\Carbon;

class CombatLogActivityEventService implements CombatLogActivityEventServiceInterface
{
    public function __construct(
        private readonly CombatLogSpellPropertyObservationRepositoryInterface     $spellPropertyObservationRepository,
        private readonly CombatLogNpcCharacteristicObservationRepositoryInterface $npcCharacteristicObservationRepository,
        private readonly CombatLogActivityEventServiceLoggingInterface            $log,
    ) {
    }

    public function recordSpellPropertyAdded(int $spellId, SpellProperty $property, ?string $combatLogPath): ?CombatLogSpellEvent
    {
        $density = null;
        if ($this->isGateEnabled()) {
            $density = $this->spellPropertyObservationRepository->getDensity($spellId, $property, Carbon::today());
            if (!$this->isAddMeaningful($density)) {
                $this->log->addSuppressed('spell', $spellId, $property->value, $density->factDays, $density->subjectDays);

                return null;
            }
        }

        return $this->createSpellEvent($spellId, CombatLogSpellEventType::PropertyChanged, $property, $combatLogPath, $density);
    }

    public function recordSpellPropertyRemoved(int $spellId, SpellProperty $property, Carbon $stalenessCutoff): ?CombatLogSpellEvent
    {
        $density = null;
        if ($this->isGateEnabled()) {
            $density = $this->spellPropertyObservationRepository->getDensity($spellId, $property, null, $stalenessCutoff);
            if (!$this->isRemovalMeaningful($density)) {
                $this->log->removalSuppressed('spell', $spellId, $property->value, $density->factDays, $density->subjectDays, $density->recentSubjectDays);

                return null;
            }
        }

        return $this->createSpellEvent($spellId, CombatLogSpellEventType::PropertyRemoved, $property, null, $density);
    }

    public function recordNpcCharacteristicAdded(int $npcId, int $characteristicId, ?string $combatLogPath): ?CombatLogNpcEvent
    {
        $density = null;
        if ($this->isGateEnabled()) {
            $density = $this->npcCharacteristicObservationRepository->getDensity($npcId, $characteristicId, Carbon::today());
            if (!$this->isAddMeaningful($density)) {
                $this->log->addSuppressed('npc', $npcId, (string)$characteristicId, $density->factDays, $density->subjectDays);

                return null;
            }
        }

        return $this->createNpcEvent($npcId, CombatLogNpcEventType::CharacteristicAdded, $characteristicId, $combatLogPath, $density);
    }

    public function recordNpcCharacteristicRemoved(int $npcId, int $characteristicId, Carbon $stalenessCutoff): ?CombatLogNpcEvent
    {
        $density = null;
        if ($this->isGateEnabled()) {
            $density = $this->npcCharacteristicObservationRepository->getDensity($npcId, $characteristicId, null, $stalenessCutoff);
            if (!$this->isRemovalMeaningful($density)) {
                $this->log->removalSuppressed('npc', $npcId, (string)$characteristicId, $density->factDays, $density->subjectDays, $density->recentSubjectDays);

                return null;
            }
        }

        return $this->createNpcEvent($npcId, CombatLogNpcEventType::CharacteristicRemoved, $characteristicId, null, $density);
    }

    /**
     * An add is news when the subject has enough history and the fact is either new to it or a reliable fact
     * returning. A rare fact that expired and came back is the same weak evidence as its expiry was.
     *
     * @param ObservationDensity $density measured over the days before today
     */
    private function isAddMeaningful(ObservationDensity $density): bool
    {
        if ($density->subjectDays < $this->minSubjectDays()) {
            return false;
        }

        return $density->factDays === 0 || $density->factRatio() >= $this->minFactRatio();
    }

    /**
     * A loss is news only when the subject was still being observed during the staleness window - otherwise the
     * silence says nothing about the fact - and the fact was seen on most of the subject's days.
     */
    private function isRemovalMeaningful(ObservationDensity $density): bool
    {
        return $density->recentSubjectDays > 0
            && $density->subjectDays >= $this->minSubjectDays()
            && $density->factRatio() >= $this->minFactRatio();
    }

    private function createSpellEvent(
        int                     $spellId,
        CombatLogSpellEventType $eventType,
        SpellProperty           $property,
        ?string                 $combatLogPath,
        ?ObservationDensity     $density,
    ): CombatLogSpellEvent {
        return CombatLogSpellEvent::create([
            'spell_id'        => $spellId,
            'event_type'      => $eventType,
            'property'        => $property,
            'combat_log_path' => $combatLogPath,
            'fact_days'       => $density?->factDays,
            'subject_days'    => $density?->subjectDays,
        ]);
    }

    private function createNpcEvent(
        int                   $npcId,
        CombatLogNpcEventType $eventType,
        int                   $characteristicId,
        ?string               $combatLogPath,
        ?ObservationDensity   $density,
    ): CombatLogNpcEvent {
        return CombatLogNpcEvent::create([
            'npc_id'          => $npcId,
            'event_type'      => $eventType,
            'model_class'     => Characteristic::class,
            'model_id'        => $characteristicId,
            'combat_log_path' => $combatLogPath,
            'fact_days'       => $density?->factDays,
            'subject_days'    => $density?->subjectDays,
        ]);
    }

    private function isGateEnabled(): bool
    {
        return (bool)config('keystoneguru.combat_log_staleness.event_density_gate_enabled');
    }

    private function minSubjectDays(): int
    {
        return (int)config('keystoneguru.combat_log_staleness.event_min_subject_days');
    }

    private function minFactRatio(): float
    {
        return (float)config('keystoneguru.combat_log_staleness.event_min_fact_ratio');
    }
}
