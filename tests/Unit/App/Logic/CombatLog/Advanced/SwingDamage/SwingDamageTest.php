<?php

namespace Tests\Unit\App\Logic\CombatLog\Advanced\SwingDamage;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedData;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\CombatEvents\Prefixes\Swing;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage;
use App\Logic\CombatLog\CombatLogEntry;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class SwingDamageTest extends TestCase
{

    /**
     * @test
     * @return void
     * @group CombatLog
     * @group SwingDamage
     * @dataProvider parseEvent_ShouldReturnAdvancedSwingDamageEvent_GivenAdvancedSwingDamageEvent_DataProvider
     */
    public function parseEvent_ShouldReturnAdvancedSwingDamageEvent_GivenAdvancedSwingDamageEvent(
        string $advancedSwingDamageEvent
    )
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedSwingDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(GenericData::class, $parseEventResult->getGenericData());
        Assert::assertInstanceOf(Swing::class, $parseEventResult->getPrefix());
        Assert::assertInstanceOf(Damage::class, $parseEventResult->getSuffix());
        Assert::assertInstanceOf(AdvancedData::class, $parseEventResult->getAdvancedData());
    }

    /**
     * @test
     * @return void
     * @group CombatLog
     * @group SwingDamage
     * @dataProvider parseEvent_ShouldReturnValidSwingDamageEvent_GivenAdvancedSwingDamageEvent_DataProvider
     */
    public function parseEvent_ShouldReturnValidSwingDamageEvent_GivenAdvancedSwingDamageEvent(
        string $advancedSwingDamageEvent,
        int    $expectedAmount,
        int    $expectedRawAmount,
        int    $expectedOverKill,
        int    $expectedSchool,
        int    $expectedResisted,
        int    $expectedBlocked,
        int    $expectedAbsorbed,
        bool   $expectedIsCritical,
        bool   $expectedIsGlancing,
        bool   $expectedIsCrushing
    )
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedSwingDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();
        /** @var Damage $damageEvent */
        $damageEvent = $parseEventResult->getSuffix();

        // Assert
        Assert::assertEquals($expectedAmount, $damageEvent->getAmount());
        Assert::assertEquals($expectedRawAmount, $damageEvent->getRawAmount());
        Assert::assertEquals($expectedOverKill, $damageEvent->getOverKill());
        Assert::assertEquals($expectedSchool, $damageEvent->getSchool());
        Assert::assertEquals($expectedResisted, $damageEvent->getResisted());
        Assert::assertEquals($expectedBlocked, $damageEvent->getBlocked());
        Assert::assertEquals($expectedAbsorbed, $damageEvent->getAbsorbed());
        Assert::assertEquals($expectedIsCritical, $damageEvent->isCritical());
        Assert::assertEquals($expectedIsGlancing, $damageEvent->isGlancing());
        Assert::assertEquals($expectedIsCrushing, $damageEvent->isCrushing());
    }

    public function parseEvent_ShouldReturnAdvancedSwingDamageEvent_GivenAdvancedSwingDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:26:51.719  SWING_DAMAGE,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,Creature-0-4242-1841-14566-133852-00036285EB,"Living Rot",0xa48,0x0,Player-1084-0A5F4542,0000000000000000,609800,640290,10680,2174,28198,4324,0,50000,50000,0,854.49,1031.35,1041,5.4011,410,7155,5111,-1,1,0,0,0,1,nil,nil',
            ], [
                '5/15 21:26:50.766  SWING_DAMAGE,Pet-0-4242-1841-14566-165189-02039CE049,"Devilsaur",0x1112,0x0,Creature-0-4242-1841-14566-133852-00036285EB,"Living Rot",0xa48,0x0,Pet-0-4242-1841-14566-165189-02039CE049,Player-1084-0A4BFB68,313498,313498,6433,6433,9408,0,2,89,100,0,860.35,1026.99,1041,3.2503,418,1923,2748,-1,1,0,0,0,nil,nil,nil',
            ],
        ];
    }

    public function parseEvent_ShouldReturnValidSwingDamageEvent_GivenAdvancedSwingDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:26:51.719  SWING_DAMAGE,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,Creature-0-4242-1841-14566-133852-00036285EB,"Living Rot",0xa48,0x0,Player-1084-0A5F4542,0000000000000000,609800,640290,10680,2174,28198,4324,0,50000,50000,0,854.49,1031.35,1041,5.4011,410,7155,5111,-1,1,0,0,0,1,nil,nil',
                7155,
                5111,
                -1,
                1,
                0,
                0,
                0,
                true,
                false,
                false,
            ], [
                '5/15 21:26:50.766  SWING_DAMAGE,Pet-0-4242-1841-14566-165189-02039CE049,"Devilsaur",0x1112,0x0,Creature-0-4242-1841-14566-133852-00036285EB,"Living Rot",0xa48,0x0,Pet-0-4242-1841-14566-165189-02039CE049,Player-1084-0A4BFB68,313498,313498,6433,6433,9408,0,2,89,100,0,860.35,1026.99,1041,3.2503,418,1923,2748,-1,1,0,0,0,nil,nil,nil',
                1923,
                2748,
                -1,
                1,
                0,
                0,
                0,
                false,
                false,
                false,
            ],
        ];
    }
}
