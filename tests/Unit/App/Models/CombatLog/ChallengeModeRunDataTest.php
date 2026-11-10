<?php

namespace Tests\Unit\App\Models\CombatLog;

use App\Models\CombatLog\ChallengeModeRunData;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
#[Group('CombatLog')]
final class ChallengeModeRunDataTest extends PublicTestCase
{
    #[Test]
    public function toArray_givenRunDataWithPostBody_omitsThePostBody(): void
    {
        // Arrange
        $challengeModeRunData = new ChallengeModeRunData([
            'challenge_mode_run_id' => 1,
            'run_id'                => 'run-id',
            'correlation_id'        => 'correlation-id',
            'post_body'             => '{"marker":"post-body"}',
            'processed'             => false,
        ]);

        // Act
        $array = $challengeModeRunData->toArray();

        // Assert
        $this->assertArrayHasKey('run_id', $array);
        $this->assertArrayNotHasKey('post_body', $array);
        $this->assertSame('{"marker":"post-body"}', $challengeModeRunData->post_body);
    }
}
