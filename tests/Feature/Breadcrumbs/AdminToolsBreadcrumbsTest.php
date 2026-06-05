<?php

namespace Tests\Feature\Breadcrumbs;

use Diglactic\Breadcrumbs\Breadcrumbs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminToolsBreadcrumbsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function adminToolsBreadcrumbKeyProvider(): array
    {
        return [
            'tools list'                                   => ['admin.tools.list'],
            'datadump view exported dungeondata'           => ['admin.tools.datadump.viewexporteddungeondata'],
            'datadump view exported release'               => ['admin.tools.datadump.viewexportedrelease'],
            'exception select'                             => ['admin.tools.exception.select'],
            'mdt diff'                                     => ['admin.tools.mdt.diff'],
            'mdt string'                                   => ['admin.tools.mdt.string'],
            'npc import'                                   => ['admin.tools.npc.import'],
            'spells show missing spell info'               => ['admin.tools.spells.showmissingspellinfo'],
            'npc show missing display id'                  => ['admin.tools.npc.showmissingdisplayid'],
            'thumbnails regenerate'                        => ['admin.tools.thumbnails.regenerate'],
            'combatlog regenerate'                         => ['admin.tools.combatlog.regenerate'],
            'combatlog criteria'                           => ['admin.tools.combatlog.criteria'],
            'dungeonroute view'                            => ['admin.tools.dungeonroute.view'],
            'dungeonroute view contents'                   => ['admin.tools.dungeonroute.viewcontents'],
            'dungeonroute mapping version usage'           => ['admin.tools.dungeonroute.mappingversions'],
            'enemyforces import'                           => ['admin.tools.enemyforces.import'],
            'enemyforces recalculate'                      => ['admin.tools.enemyforces.recalculate'],
            'features list'                                => ['admin.tools.features.list'],
            'mdt dungeon mapping hash'                     => ['admin.tools.mdt.dungeonmappinghash'],
            'mdt dungeon mapping version accuracy'         => ['admin.tools.mdt.dungeonmappingversionaccuracy'],
            'mdt dungeon mapping version to mapping'       => ['admin.tools.mdt.dungeonmappingversiontomdtmapping'],
            'mdt dungeonroute'                             => ['admin.tools.mdt.dungeonroute'],
            'mdt list'                                     => ['admin.tools.mdt.list'],
            'messagebanner set'                            => ['admin.tools.messagebanner.set'],
            'npc manage spell visibility'                  => ['admin.tools.npc.managespellvisibility'],
            'wagogg import ingame coordinates'             => ['admin.tools.wagogg.importingamecoordinates'],
            'artisan commands backfill kill zone enemy id' => ['admin.tools.artisancommands.backfillkillzoneenemyid'],
        ];
    }

    #[Test]
    #[DataProvider('adminToolsBreadcrumbKeyProvider')]
    public function breadcrumbExists_givenAdminToolsKey_returnsTrue(string $breadcrumbKey): void
    {
        // Arrange: breadcrumb key is provided via data provider

        // Act
        $exists = Breadcrumbs::exists($breadcrumbKey);

        // Assert
        $this->assertTrue($exists, sprintf('Breadcrumb key "%s" is not registered', $breadcrumbKey));
    }

    #[Test]
    #[DataProvider('adminToolsBreadcrumbKeyProvider')]
    public function breadcrumbGenerate_givenAdminToolsKey_generatesWithoutError(string $breadcrumbKey): void
    {
        // Arrange: breadcrumb key is provided via data provider

        // Act
        $breadcrumbs = Breadcrumbs::generate($breadcrumbKey);

        // Assert
        $this->assertNotEmpty($breadcrumbs, sprintf('Breadcrumb key "%s" generated an empty trail', $breadcrumbKey));
    }
}
