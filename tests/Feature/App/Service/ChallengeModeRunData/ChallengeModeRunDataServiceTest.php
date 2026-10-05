<?php

namespace Tests\Feature\App\Service\ChallengeModeRunData;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogEvent;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

#[Group('ChallengeModeRunData')]
final class ChallengeModeRunDataServiceTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const string FIXTURE_NAME = 'TWW/tww_s1_ara_kara_city_of_echoes_3';

    private const string FIXTURE_ROOT_PATH = '../../../Controller/Api/V1/APICombatLogController/';

    #[Test]
    public function convertChallengeModeRunData_givenRunWithoutTheOptionalRunFields_insertsItsEvents(): void
    {
        $runId                = Str::uuid()->toString();
        $challengeModeRunData = null;

        try {
            // Arrange - the combat log route request still accepts these fields as null
            $postBody                      = $this->getJsonData(self::FIXTURE_NAME, self::FIXTURE_ROOT_PATH);
            $postBody['metadata']['runId'] = $runId;
            unset(
                $postBody['metadata']['period'],
                $postBody['metadata']['season'],
                $postBody['metadata']['realmType'],
                $postBody['challengeMode']['parTimeMs'],
                $postBody['challengeMode']['timerFraction'],
                $postBody['challengeMode']['numDeaths'],
            );
            $challengeModeRunData = ChallengeModeRunData::create([
                'challenge_mode_run_id' => -1,
                'run_id'                => $runId,
                'correlation_id'        => $runId,
                'post_body'             => json_encode($postBody),
                'processed'             => false,
            ]);

            // Act
            $result = app(ChallengeModeRunDataServiceInterface::class)->convertChallengeModeRunData($challengeModeRunData);

            // Assert
            $this->assertTrue($result);
            /** @var CombatLogEvent $combatLogEvent */
            $combatLogEvent = CombatLogEvent::query()->where('run_id', $runId)->firstOrFail();
            $this->assertSame('', $combatLogEvent->season);
            $this->assertSame(0, (int)$combatLogEvent->num_deaths);
        } finally {
            CombatLogEvent::query()->where('run_id', $runId)->delete();
            $challengeModeRunData?->delete();
        }
    }
}
