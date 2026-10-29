<?php

namespace Tests\Feature\View\Compendium;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Compendium')]
final class EventListTest extends PublicTestCase
{
    private const int NPC_ID = 9995199;

    #[Test]
    public function render_givenEventWithDensity_showsTheEvidenceLine(): void
    {
        // Arrange
        $event = $this->makeNpcEvent(18, 22);

        // Act
        $result = $this->render($event);

        // Assert
        $this->assertStringContainsString('compendium_log_evidence', $result);
        $this->assertStringContainsString(
            __('view_compendium.event.evidence_npc', ['fact' => 18, 'subject' => 22]),
            $result,
        );
    }

    #[Test]
    public function render_givenEventForAFactWithNoEarlierDays_showsTheFirstSeenLine(): void
    {
        // Arrange
        $event = $this->makeNpcEvent(0, 22, CombatLogNpcEventType::CharacteristicAdded);

        // Act
        $result = $this->render($event);

        // Assert
        $this->assertStringContainsString(trans_choice('view_compendium.event.evidence_new_npc', 22, ['subject' => 22]), $result);
        $this->assertStringNotContainsString(
            __('view_compendium.event.evidence_npc', ['fact' => 0, 'subject' => 22]),
            $result,
        );
    }

    #[Test]
    public function render_givenEventForAFactOnASubjectSeenOnOneEarlierDay_showsTheSingularFirstSeenLine(): void
    {
        // Arrange - the common case now that a new fact needs only one earlier day of its subject
        $event = $this->makeNpcEvent(0, 1, CombatLogNpcEventType::CharacteristicAdded);

        // Act
        $result = $this->render($event);

        // Assert
        $this->assertStringContainsString(trans_choice('view_compendium.event.evidence_new_npc', 1, ['subject' => 1]), $result);
        $this->assertStringNotContainsString(trans_choice('view_compendium.event.evidence_new_npc', 2, ['subject' => 1]), $result);
    }

    #[Test]
    public function render_givenRemovalWithNoFactDays_showsTheSeenOnLine(): void
    {
        // Arrange - only reachable with event_min_fact_ratio at 0; a removal is never a first sighting
        $event = $this->makeNpcEvent(0, 22);

        // Act
        $result = $this->render($event);

        // Assert
        $this->assertStringContainsString(__('view_compendium.event.evidence_npc', ['fact' => 0, 'subject' => 22]), $result);
        $this->assertStringNotContainsString(trans_choice('view_compendium.event.evidence_new_npc', 22, ['subject' => 22]), $result);
    }

    #[Test]
    public function render_givenEventWithoutDensity_showsNoEvidenceLine(): void
    {
        // Arrange - events written before the gate, or with the gate off, carry no density
        $event = $this->makeNpcEvent(null, null);

        // Act
        $result = $this->render($event);

        // Assert
        $this->assertStringContainsString('compendium_log_row', $result);
        $this->assertStringNotContainsString('compendium_log_evidence', $result);
    }

    private function makeNpcEvent(
        ?int                  $factDays,
        ?int                  $subjectDays,
        CombatLogNpcEventType $eventType = CombatLogNpcEventType::CharacteristicRemoved,
    ): CombatLogNpcEvent {
        $event = new CombatLogNpcEvent([
            'npc_id'       => self::NPC_ID,
            'event_type'   => $eventType,
            'model_class'  => Characteristic::class,
            'model_id'     => Characteristic::ALL[Characteristic::CHARACTERISTIC_SILENCE],
            'fact_days'    => $factDays,
            'subject_days' => $subjectDays,
        ]);
        $event->created_at = Carbon::now();

        return $event;
    }

    private function render(CombatLogNpcEvent $event): string
    {
        return view('compendium.sections.event_list', [
            'events'   => collect([$event]),
            'emptyKey' => 'view_compendium.activity.empty',
        ])->render();
    }
}
