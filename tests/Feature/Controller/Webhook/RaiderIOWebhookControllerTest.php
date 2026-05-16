<?php

namespace Tests\Feature\Controller\Webhook;

use App\Jobs\CombatLog\ProcessCombatLogFanout;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Webhook')]
final class RaiderIOWebhookControllerTest extends PublicTestCase
{
    private const string TEST_USER     = 'raiderio_test_user';
    private const string TEST_PASSWORD = 'raiderio_test_password';

    /** @var array<string, mixed> */
    private array $validPayload = [
        'challenge_mode_id'  => 402,
        'spec_ids'           => [250, 577],
        's3_bucket'          => 'raiderio-combat-logs',
        's3_path'            => 'runs/2026/05/15/abc123/',
        'combat_log_version' => 22012000005,
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keystoneguru.webhook.raiderio.user'     => self::TEST_USER,
            'keystoneguru.webhook.raiderio.password' => self::TEST_PASSWORD,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function combatLog_givenCriteriaMet_returnsAcceptedAndDispatchesJob(): void
    {
        // Arrange
        Bus::fake();
        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->once())->method('shouldParse')->willReturn(true);
        $criteriaService->expects($this->once())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        // Act
        $response = $this->withBasicAuth(self::TEST_USER, self::TEST_PASSWORD)
            ->postJson(route('webhook.raiderio.combatlog'), $this->validPayload);

        // Assert
        $response->assertStatus(202);
        Bus::assertDispatched(ProcessCombatLogFanout::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function combatLog_givenCriteriaNotMet_returnsNoContentAndDoesNotDispatchJob(): void
    {
        // Arrange
        Bus::fake();
        $criteriaService = $this->createMockPublic(CombatLogParsingCriteriaServiceInterface::class);
        $criteriaService->expects($this->once())->method('shouldParse')->willReturn(false);
        $criteriaService->expects($this->never())->method('recordParsed');
        app()->instance(CombatLogParsingCriteriaServiceInterface::class, $criteriaService);

        // Act
        $response = $this->withBasicAuth(self::TEST_USER, self::TEST_PASSWORD)
            ->postJson(route('webhook.raiderio.combatlog'), $this->validPayload);

        // Assert
        $response->assertNoContent();
        Bus::assertNotDispatched(ProcessCombatLogFanout::class);
    }

    #[Test]
    public function combatLog_givenInvalidCredentials_returnsUnauthorized(): void
    {
        // Arrange
        Bus::fake();

        // Act
        $response = $this->withBasicAuth('wrong_user', 'wrong_password')
            ->postJson(route('webhook.raiderio.combatlog'), $this->validPayload);

        // Assert
        $response->assertStatus(401);
        Bus::assertNotDispatched(ProcessCombatLogFanout::class);
    }

    #[Test]
    public function combatLog_givenMissingCredentials_returnsUnauthorized(): void
    {
        // Arrange
        Bus::fake();

        // Act
        $response = $this->postJson(route('webhook.raiderio.combatlog'), $this->validPayload);

        // Assert
        $response->assertStatus(401);
        Bus::assertNotDispatched(ProcessCombatLogFanout::class);
    }

    #[Test]
    public function combatLog_givenMissingRequiredFields_returnsUnprocessableEntity(): void
    {
        // Arrange
        Bus::fake();

        // Act
        $response = $this->withBasicAuth(self::TEST_USER, self::TEST_PASSWORD)
            ->postJson(route('webhook.raiderio.combatlog'), []);

        // Assert
        $response->assertUnprocessable();
        Bus::assertNotDispatched(ProcessCombatLogFanout::class);
    }

    #[Test]
    public function combatLog_givenUnknownChallengeModeId_returnsUnprocessableEntity(): void
    {
        // Arrange
        Bus::fake();

        // Act
        $response = $this->withBasicAuth(self::TEST_USER, self::TEST_PASSWORD)
            ->postJson(route('webhook.raiderio.combatlog'), array_merge($this->validPayload, [
                'challenge_mode_id' => 999999,
            ]));

        // Assert
        $response->assertUnprocessable();
        Bus::assertNotDispatched(ProcessCombatLogFanout::class);
    }

    #[Test]
    public function combatLog_givenUnknownSpecId_returnsUnprocessableEntity(): void
    {
        // Arrange
        Bus::fake();

        // Act
        $response = $this->withBasicAuth(self::TEST_USER, self::TEST_PASSWORD)
            ->postJson(route('webhook.raiderio.combatlog'), array_merge($this->validPayload, [
                'spec_ids' => [999999],
            ]));

        // Assert
        $response->assertUnprocessable();
        Bus::assertNotDispatched(ProcessCombatLogFanout::class);
    }
}
