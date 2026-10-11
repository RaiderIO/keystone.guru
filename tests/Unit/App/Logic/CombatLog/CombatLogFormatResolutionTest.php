<?php

namespace Tests\Unit\App\Logic\CombatLog;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataBuilder;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V20\AdvancedDataV20;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V22\AdvancedDataV22;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9\AdvancedDataV9;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9SoD\AdvancedDataV9SoD;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22\AuraAppliedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22_1\AuraAppliedV22_1;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\AuraRemovedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\V22\AuraRemovedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\V22_1\AuraRemovedV22_1;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\DamageBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\DamageLandedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V20\DamageLandedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V22\DamageLandedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\DamageLandedSupportBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\V20\DamageLandedSupportV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\V22\DamageLandedSupportV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V20\DamageSupportV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V22\DamageSupportV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\EnergizeBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V22\EnergizeV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V9\EnergizeV9;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V20\MissedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V22\MissedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V9SoD\MissedV9SoD;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\CombatantInfoBuilder;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V21\CombatantInfoV21;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V22\CombatantInfoV22;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V9SoD\CombatantInfoV9SoD;
use App\Logic\CombatLog\SpecialEvents\DamageShield\DamageShieldBuilder;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V20\DamageShieldV20;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V22\DamageShieldV22;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9\DamageShieldV9;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9SoD\DamageShieldV9SoD;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\DamageShieldMissedBuilder;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions\V20\DamageShieldMissedV20;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions\V9\DamageShieldMissedV9;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\DamageSplitBuilder;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V20\DamageSplitV20;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V22\DamageSplitV22;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V9SoD\DamageSplitV9SoD;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndBuilder;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V20\EncounterEndV20;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V9\EncounterEndV9;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\EncounterStartBuilder;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V20\EncounterStartV20;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V9\EncounterStartV9;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\EnvironmentalDamageBuilder;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V20\EnvironmentalDamageV20;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V22\EnvironmentalDamageV22;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9\EnvironmentalDamageV9;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9SoD\EnvironmentalDamageV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\SpellAbsorbedBuilder;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V20\SpellAbsorbedV20;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V9\SpellAbsorbedV9;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\SpellAbsorbedSupportBuilder;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\Versions\V20\SpellAbsorbedSupportV20;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\Versions\V9\SpellAbsorbedSupportV9;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogVersion')]
final class CombatLogFormatResolutionTest extends PublicTestCase
{
    /**
     * @param class-string $builderClassName
     * @param class-string $expectedClassName
     */
    #[Test]
    #[DataProvider('resolveClassName_givenARegisteredVersionAroundAFormatChange_returnsItsFormat_DataProvider')]
    public function resolveClassName_givenARegisteredVersionAroundAFormatChange_returnsItsFormat(
        string $builderClassName,
        int    $combatLogVersion,
        string $expectedClassName,
    ): void {
        // Act
        $result = $builderClassName::resolveClassName($combatLogVersion);

        // Assert
        Assert::assertSame($expectedClassName, $result);
    }

    /**
     * Per builder and lineage: the oldest and newest registered version, and the registered versions
     * on either side of every format change.
     *
     * @return array<string, array{0: class-string, 1: int, 2: class-string}>
     */
    public static function resolveClassName_givenARegisteredVersionAroundAFormatChange_returnsItsFormat_DataProvider(): array
    {
        return [
            'AdvancedDataBuilder CLASSIC'                   => [AdvancedDataBuilder::class, CombatLogVersion::CLASSIC, AdvancedDataV9::class],
            'AdvancedDataBuilder CLASSIC_SOD_1_15_5'        => [AdvancedDataBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, AdvancedDataV9SoD::class],
            'AdvancedDataBuilder CLASSIC_TBC_2_5_5'         => [AdvancedDataBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, AdvancedDataV9SoD::class],
            'AdvancedDataBuilder RETAIL_10_1_0'             => [AdvancedDataBuilder::class, CombatLogVersion::RETAIL_10_1_0, AdvancedDataV20::class],
            'AdvancedDataBuilder RETAIL_11_0_2'             => [AdvancedDataBuilder::class, CombatLogVersion::RETAIL_11_0_2, AdvancedDataV20::class],
            'AdvancedDataBuilder RETAIL_11_0_5'             => [AdvancedDataBuilder::class, CombatLogVersion::RETAIL_11_0_5, AdvancedDataV22::class],
            'AdvancedDataBuilder RETAIL_12_1_0'             => [AdvancedDataBuilder::class, CombatLogVersion::RETAIL_12_1_0, AdvancedDataV22::class],
            'AuraAppliedBuilder CLASSIC'                    => [AuraAppliedBuilder::class, CombatLogVersion::CLASSIC, AuraAppliedV22::class],
            'AuraAppliedBuilder CLASSIC_TBC_2_5_5'          => [AuraAppliedBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, AuraAppliedV22::class],
            'AuraAppliedBuilder RETAIL_10_1_0'              => [AuraAppliedBuilder::class, CombatLogVersion::RETAIL_10_1_0, AuraAppliedV22::class],
            'AuraAppliedBuilder RETAIL_11_1_7'              => [AuraAppliedBuilder::class, CombatLogVersion::RETAIL_11_1_7, AuraAppliedV22::class],
            'AuraAppliedBuilder RETAIL_11_2_0'              => [AuraAppliedBuilder::class, CombatLogVersion::RETAIL_11_2_0, AuraAppliedV22_1::class],
            'AuraAppliedBuilder RETAIL_12_1_0'              => [AuraAppliedBuilder::class, CombatLogVersion::RETAIL_12_1_0, AuraAppliedV22_1::class],
            'AuraRemovedBuilder CLASSIC'                    => [AuraRemovedBuilder::class, CombatLogVersion::CLASSIC, AuraRemovedV22::class],
            'AuraRemovedBuilder CLASSIC_TBC_2_5_5'          => [AuraRemovedBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, AuraRemovedV22::class],
            'AuraRemovedBuilder RETAIL_10_1_0'              => [AuraRemovedBuilder::class, CombatLogVersion::RETAIL_10_1_0, AuraRemovedV22::class],
            'AuraRemovedBuilder RETAIL_11_1_7'              => [AuraRemovedBuilder::class, CombatLogVersion::RETAIL_11_1_7, AuraRemovedV22::class],
            'AuraRemovedBuilder RETAIL_11_2_0'              => [AuraRemovedBuilder::class, CombatLogVersion::RETAIL_11_2_0, AuraRemovedV22_1::class],
            'AuraRemovedBuilder RETAIL_12_1_0'              => [AuraRemovedBuilder::class, CombatLogVersion::RETAIL_12_1_0, AuraRemovedV22_1::class],
            'DamageLandedSupportBuilder CLASSIC'            => [DamageLandedSupportBuilder::class, CombatLogVersion::CLASSIC, DamageLandedSupportV20::class],
            'DamageLandedSupportBuilder CLASSIC_TBC_2_5_5'  => [DamageLandedSupportBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageLandedSupportV20::class],
            'DamageLandedSupportBuilder RETAIL_10_1_0'      => [DamageLandedSupportBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageLandedSupportV20::class],
            'DamageLandedSupportBuilder RETAIL_11_0_2'      => [DamageLandedSupportBuilder::class, CombatLogVersion::RETAIL_11_0_2, DamageLandedSupportV20::class],
            'DamageLandedSupportBuilder RETAIL_11_0_5'      => [DamageLandedSupportBuilder::class, CombatLogVersion::RETAIL_11_0_5, DamageLandedSupportV22::class],
            'DamageLandedSupportBuilder RETAIL_12_1_0'      => [DamageLandedSupportBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageLandedSupportV22::class],
            'DamageLandedBuilder CLASSIC'                   => [DamageLandedBuilder::class, CombatLogVersion::CLASSIC, DamageLandedV20::class],
            'DamageLandedBuilder CLASSIC_TBC_2_5_5'         => [DamageLandedBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageLandedV20::class],
            'DamageLandedBuilder RETAIL_10_1_0'             => [DamageLandedBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageLandedV20::class],
            'DamageLandedBuilder RETAIL_11_0_2'             => [DamageLandedBuilder::class, CombatLogVersion::RETAIL_11_0_2, DamageLandedV20::class],
            'DamageLandedBuilder RETAIL_11_0_5'             => [DamageLandedBuilder::class, CombatLogVersion::RETAIL_11_0_5, DamageLandedV22::class],
            'DamageLandedBuilder RETAIL_12_1_0'             => [DamageLandedBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageLandedV22::class],
            'DamageSupportBuilder CLASSIC'                  => [DamageSupportBuilder::class, CombatLogVersion::CLASSIC, DamageSupportV20::class],
            'DamageSupportBuilder CLASSIC_TBC_2_5_5'        => [DamageSupportBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageSupportV20::class],
            'DamageSupportBuilder RETAIL_10_1_0'            => [DamageSupportBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageSupportV20::class],
            'DamageSupportBuilder RETAIL_11_0_2'            => [DamageSupportBuilder::class, CombatLogVersion::RETAIL_11_0_2, DamageSupportV20::class],
            'DamageSupportBuilder RETAIL_11_0_5'            => [DamageSupportBuilder::class, CombatLogVersion::RETAIL_11_0_5, DamageSupportV22::class],
            'DamageSupportBuilder RETAIL_12_1_0'            => [DamageSupportBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageSupportV22::class],
            'DamageBuilder CLASSIC'                         => [DamageBuilder::class, CombatLogVersion::CLASSIC, DamageV20::class],
            'DamageBuilder CLASSIC_SOD_1_15_5'              => [DamageBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, DamageV22::class],
            'DamageBuilder CLASSIC_TBC_2_5_5'               => [DamageBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageV22::class],
            'DamageBuilder RETAIL_10_1_0'                   => [DamageBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageV20::class],
            'DamageBuilder RETAIL_11_0_2'                   => [DamageBuilder::class, CombatLogVersion::RETAIL_11_0_2, DamageV20::class],
            'DamageBuilder RETAIL_11_0_5'                   => [DamageBuilder::class, CombatLogVersion::RETAIL_11_0_5, DamageV22::class],
            'DamageBuilder RETAIL_12_1_0'                   => [DamageBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageV22::class],
            'EnergizeBuilder CLASSIC'                       => [EnergizeBuilder::class, CombatLogVersion::CLASSIC, EnergizeV22::class],
            'EnergizeBuilder CLASSIC_SOD_1_15_5'            => [EnergizeBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, EnergizeV9::class],
            'EnergizeBuilder CLASSIC_SOD_1_15_6'            => [EnergizeBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_6, EnergizeV9::class],
            'EnergizeBuilder CLASSIC_SOD_1_15_7'            => [EnergizeBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_7, EnergizeV9::class],
            'EnergizeBuilder CLASSIC_TBC_2_5_5'             => [EnergizeBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, EnergizeV9::class],
            'EnergizeBuilder RETAIL_10_1_0'                 => [EnergizeBuilder::class, CombatLogVersion::RETAIL_10_1_0, EnergizeV22::class],
            'EnergizeBuilder RETAIL_12_1_0'                 => [EnergizeBuilder::class, CombatLogVersion::RETAIL_12_1_0, EnergizeV22::class],
            'MissedBuilder CLASSIC'                         => [MissedBuilder::class, CombatLogVersion::CLASSIC, MissedV20::class],
            'MissedBuilder CLASSIC_SOD_1_15_5'              => [MissedBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, MissedV9SoD::class],
            'MissedBuilder CLASSIC_TBC_2_5_5'               => [MissedBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, MissedV9SoD::class],
            'MissedBuilder RETAIL_10_1_0'                   => [MissedBuilder::class, CombatLogVersion::RETAIL_10_1_0, MissedV20::class],
            'MissedBuilder RETAIL_11_0_2'                   => [MissedBuilder::class, CombatLogVersion::RETAIL_11_0_2, MissedV20::class],
            'MissedBuilder RETAIL_11_0_5'                   => [MissedBuilder::class, CombatLogVersion::RETAIL_11_0_5, MissedV22::class],
            'MissedBuilder RETAIL_12_1_0'                   => [MissedBuilder::class, CombatLogVersion::RETAIL_12_1_0, MissedV22::class],
            'CombatantInfoBuilder CLASSIC'                  => [CombatantInfoBuilder::class, CombatLogVersion::CLASSIC, CombatantInfoV22::class],
            'CombatantInfoBuilder CLASSIC_SOD_1_15_5'       => [CombatantInfoBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, CombatantInfoV9SoD::class],
            'CombatantInfoBuilder CLASSIC_TBC_2_5_5'        => [CombatantInfoBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, CombatantInfoV9SoD::class],
            'CombatantInfoBuilder RETAIL_10_1_0'            => [CombatantInfoBuilder::class, CombatLogVersion::RETAIL_10_1_0, CombatantInfoV21::class],
            'CombatantInfoBuilder RETAIL_11_2_0'            => [CombatantInfoBuilder::class, CombatLogVersion::RETAIL_11_2_0, CombatantInfoV21::class],
            'CombatantInfoBuilder RETAIL_12_0_1'            => [CombatantInfoBuilder::class, CombatLogVersion::RETAIL_12_0_1, CombatantInfoV22::class],
            'CombatantInfoBuilder RETAIL_12_1_0'            => [CombatantInfoBuilder::class, CombatLogVersion::RETAIL_12_1_0, CombatantInfoV22::class],
            'DamageShieldMissedBuilder CLASSIC'             => [DamageShieldMissedBuilder::class, CombatLogVersion::CLASSIC, DamageShieldMissedV9::class],
            'DamageShieldMissedBuilder CLASSIC_SOD_1_15_5'  => [DamageShieldMissedBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, DamageShieldMissedV20::class],
            'DamageShieldMissedBuilder CLASSIC_TBC_2_5_5'   => [DamageShieldMissedBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageShieldMissedV20::class],
            'DamageShieldMissedBuilder RETAIL_10_1_0'       => [DamageShieldMissedBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageShieldMissedV20::class],
            'DamageShieldMissedBuilder RETAIL_12_1_0'       => [DamageShieldMissedBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageShieldMissedV20::class],
            'DamageShieldBuilder CLASSIC'                   => [DamageShieldBuilder::class, CombatLogVersion::CLASSIC, DamageShieldV9::class],
            'DamageShieldBuilder CLASSIC_SOD_1_15_5'        => [DamageShieldBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, DamageShieldV9SoD::class],
            'DamageShieldBuilder CLASSIC_TBC_2_5_5'         => [DamageShieldBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageShieldV9SoD::class],
            'DamageShieldBuilder RETAIL_10_1_0'             => [DamageShieldBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageShieldV20::class],
            'DamageShieldBuilder RETAIL_11_0_2'             => [DamageShieldBuilder::class, CombatLogVersion::RETAIL_11_0_2, DamageShieldV20::class],
            'DamageShieldBuilder RETAIL_11_0_5'             => [DamageShieldBuilder::class, CombatLogVersion::RETAIL_11_0_5, DamageShieldV22::class],
            'DamageShieldBuilder RETAIL_12_1_0'             => [DamageShieldBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageShieldV22::class],
            'DamageSplitBuilder CLASSIC'                    => [DamageSplitBuilder::class, CombatLogVersion::CLASSIC, DamageSplitV20::class],
            'DamageSplitBuilder CLASSIC_SOD_1_15_5'         => [DamageSplitBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, DamageSplitV9SoD::class],
            'DamageSplitBuilder CLASSIC_TBC_2_5_5'          => [DamageSplitBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, DamageSplitV9SoD::class],
            'DamageSplitBuilder RETAIL_10_1_0'              => [DamageSplitBuilder::class, CombatLogVersion::RETAIL_10_1_0, DamageSplitV20::class],
            'DamageSplitBuilder RETAIL_11_0_2'              => [DamageSplitBuilder::class, CombatLogVersion::RETAIL_11_0_2, DamageSplitV20::class],
            'DamageSplitBuilder RETAIL_11_0_5'              => [DamageSplitBuilder::class, CombatLogVersion::RETAIL_11_0_5, DamageSplitV22::class],
            'DamageSplitBuilder RETAIL_12_1_0'              => [DamageSplitBuilder::class, CombatLogVersion::RETAIL_12_1_0, DamageSplitV22::class],
            'EncounterEndBuilder CLASSIC'                   => [EncounterEndBuilder::class, CombatLogVersion::CLASSIC, EncounterEndV9::class],
            'EncounterEndBuilder CLASSIC_TBC_2_5_5'         => [EncounterEndBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, EncounterEndV9::class],
            'EncounterEndBuilder RETAIL_10_1_0'             => [EncounterEndBuilder::class, CombatLogVersion::RETAIL_10_1_0, EncounterEndV20::class],
            'EncounterEndBuilder RETAIL_12_1_0'             => [EncounterEndBuilder::class, CombatLogVersion::RETAIL_12_1_0, EncounterEndV20::class],
            'EncounterStartBuilder CLASSIC'                 => [EncounterStartBuilder::class, CombatLogVersion::CLASSIC, EncounterStartV9::class],
            'EncounterStartBuilder CLASSIC_TBC_2_5_5'       => [EncounterStartBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, EncounterStartV9::class],
            'EncounterStartBuilder RETAIL_10_1_0'           => [EncounterStartBuilder::class, CombatLogVersion::RETAIL_10_1_0, EncounterStartV20::class],
            'EncounterStartBuilder RETAIL_12_1_0'           => [EncounterStartBuilder::class, CombatLogVersion::RETAIL_12_1_0, EncounterStartV20::class],
            'EnvironmentalDamageBuilder CLASSIC'            => [EnvironmentalDamageBuilder::class, CombatLogVersion::CLASSIC, EnvironmentalDamageV9::class],
            'EnvironmentalDamageBuilder CLASSIC_SOD_1_15_5' => [EnvironmentalDamageBuilder::class, CombatLogVersion::CLASSIC_SOD_1_15_5, EnvironmentalDamageV9SoD::class],
            'EnvironmentalDamageBuilder CLASSIC_TBC_2_5_5'  => [EnvironmentalDamageBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, EnvironmentalDamageV9SoD::class],
            'EnvironmentalDamageBuilder RETAIL_10_1_0'      => [EnvironmentalDamageBuilder::class, CombatLogVersion::RETAIL_10_1_0, EnvironmentalDamageV20::class],
            'EnvironmentalDamageBuilder RETAIL_11_0_2'      => [EnvironmentalDamageBuilder::class, CombatLogVersion::RETAIL_11_0_2, EnvironmentalDamageV20::class],
            'EnvironmentalDamageBuilder RETAIL_11_0_5'      => [EnvironmentalDamageBuilder::class, CombatLogVersion::RETAIL_11_0_5, EnvironmentalDamageV22::class],
            'EnvironmentalDamageBuilder RETAIL_12_1_0'      => [EnvironmentalDamageBuilder::class, CombatLogVersion::RETAIL_12_1_0, EnvironmentalDamageV22::class],
            'SpellAbsorbedSupportBuilder CLASSIC'           => [SpellAbsorbedSupportBuilder::class, CombatLogVersion::CLASSIC, SpellAbsorbedSupportV9::class],
            'SpellAbsorbedSupportBuilder CLASSIC_TBC_2_5_5' => [SpellAbsorbedSupportBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, SpellAbsorbedSupportV9::class],
            'SpellAbsorbedSupportBuilder RETAIL_10_1_0'     => [SpellAbsorbedSupportBuilder::class, CombatLogVersion::RETAIL_10_1_0, SpellAbsorbedSupportV20::class],
            'SpellAbsorbedSupportBuilder RETAIL_12_1_0'     => [SpellAbsorbedSupportBuilder::class, CombatLogVersion::RETAIL_12_1_0, SpellAbsorbedSupportV20::class],
            'SpellAbsorbedBuilder CLASSIC'                  => [SpellAbsorbedBuilder::class, CombatLogVersion::CLASSIC, SpellAbsorbedV9::class],
            'SpellAbsorbedBuilder CLASSIC_TBC_2_5_5'        => [SpellAbsorbedBuilder::class, CombatLogVersion::CLASSIC_TBC_2_5_5, SpellAbsorbedV9::class],
            'SpellAbsorbedBuilder RETAIL_10_1_0'            => [SpellAbsorbedBuilder::class, CombatLogVersion::RETAIL_10_1_0, SpellAbsorbedV20::class],
            'SpellAbsorbedBuilder RETAIL_12_1_0'            => [SpellAbsorbedBuilder::class, CombatLogVersion::RETAIL_12_1_0, SpellAbsorbedV20::class],
        ];
    }

    #[Test]
    public function getClassNamesByFirstCombatLogVersion_givenEveryBuilder_startsEachLineageAtItsOldestSupportedVersionAndAscends(): void
    {
        // Arrange
        $builderClassNames = self::getBuilderClassNames();

        // Act & Assert
        Assert::assertGreaterThanOrEqual(18, count($builderClassNames));
        foreach ($builderClassNames as $builderClassName) {
            $classNamesByFirstCombatLogVersion = $builderClassName::getClassNamesByFirstCombatLogVersion();

            foreach (CombatLogLineage::cases() as $lineage) {
                $firstCombatLogVersions = array_keys($classNamesByFirstCombatLogVersion[$lineage->value] ?? []);

                Assert::assertSame(
                    $lineage->getOldestSupportedCombatLogVersion(),
                    $firstCombatLogVersions[0] ?? null,
                    sprintf('%s does not start its %s formats at the oldest supported version', $builderClassName, $lineage->value),
                );
                $sortedFirstCombatLogVersions = $firstCombatLogVersions;
                sort($sortedFirstCombatLogVersions);
                Assert::assertSame($sortedFirstCombatLogVersions, $firstCombatLogVersions, sprintf('%s lists its %s formats out of order', $builderClassName, $lineage->value));
                foreach ($firstCombatLogVersions as $firstCombatLogVersion) {
                    Assert::assertSame($lineage, CombatLogLineage::fromCombatLogVersion($firstCombatLogVersion), sprintf('%s lists %d under %s', $builderClassName, $firstCombatLogVersion, $lineage->value));
                }
            }
        }
    }

    #[Test]
    public function resolveClassName_givenAnUnregisteredRetailBuildNewerThanAllFormats_returnsTheNewestRetailFormat(): void
    {
        // Act
        $result = MissedBuilder::resolveClassName(22_012_009_009);

        // Assert
        Assert::assertSame(MissedV22::class, $result);
    }

    #[Test]
    public function resolveClassName_givenAnUnregisteredClassicBuildNewerThanAllFormats_returnsTheNewestClassicFormat(): void
    {
        // Act
        $result = MissedBuilder::resolveClassName(9_005_005_000);

        // Assert
        Assert::assertSame(MissedV9SoD::class, $result);
    }

    #[Test]
    public function resolveClassName_givenAnUnregisteredBuildBetweenTwoFormats_returnsTheOlderFormat(): void
    {
        // Act
        $result = MissedBuilder::resolveClassName(21_011_000_003);

        // Assert
        Assert::assertSame(MissedV20::class, $result);
    }

    #[Test]
    public function resolveClassName_givenAVersionOlderThanItsLineageSupports_throwsInvalidArgumentException(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has no retail format for combat log version 19009002007');

        // Act
        MissedBuilder::resolveClassName(19_009_002_007);
    }

    /**
     * @return list<class-string>
     */
    private static function getBuilderClassNames(): array
    {
        $builderClassNames = [];
        foreach (glob(app_path('Logic/CombatLog/{CombatEvents,CombatEvents/Suffixes,SpecialEvents}/*/*Builder.php'), GLOB_BRACE) as $filePath) {
            $className = sprintf('App\\%s', str_replace(['/', '.php'], ['\\', ''], substr($filePath, strlen(app_path()) + 1)));
            if (in_array(ResolvesCombatLogFormat::class, class_uses($className), true)) {
                $builderClassNames[] = $className;
            }
        }

        return $builderClassNames;
    }
}
