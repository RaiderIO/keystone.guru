<?php

namespace Database\Seeders;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupCoupling;
use App\Models\Expansion;
use App\Models\File;
use App\Models\Season;
use App\SeederHelpers\Traits\FindsAffixes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Str;

class AffixSeeder extends Seeder implements TableSeederInterface
{
    use FindsAffixes;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // @formatter:off

        $affixes = collect([
            new Affix(['key' => Affix::AFFIX_BOLSTERING, 'name' => 'affixes.bolstering.name', 'icon_file_id' => -1, 'affix_id' => 7, 'description' => 'affixes.bolstering.description']),
            new Affix(['key' => Affix::AFFIX_BURSTING, 'name' => 'affixes.bursting.name', 'icon_file_id' => -1, 'affix_id' => 11, 'description' => 'affixes.bursting.description']),
            new Affix(['key' => Affix::AFFIX_EXPLOSIVE, 'name' => 'affixes.explosive.name', 'icon_file_id' => -1, 'affix_id' => 13, 'description' => 'affixes.explosive.description']),
            new Affix(['key' => Affix::AFFIX_FORTIFIED, 'name' => 'affixes.fortified.name', 'icon_file_id' => -1, 'affix_id' => 10, 'description' => 'affixes.fortified.description']),
            new Affix(['key' => Affix::AFFIX_GRIEVOUS, 'name' => 'affixes.grievous.name', 'icon_file_id' => -1, 'affix_id' => 12, 'description' => 'affixes.grievous.description']),
            new Affix(['key' => Affix::AFFIX_INFESTED, 'name' => 'affixes.infested.name', 'icon_file_id' => -1, 'affix_id' => 16, 'description' => 'affixes.infested.description']),
            new Affix(['key' => Affix::AFFIX_NECROTIC, 'name' => 'affixes.necrotic.name', 'icon_file_id' => -1, 'affix_id' => 4, 'description' => 'affixes.necrotic.description']),
            new Affix(['key' => Affix::AFFIX_QUAKING, 'name' => 'affixes.quaking.name', 'icon_file_id' => -1, 'affix_id' => 14, 'description' => 'affixes.quaking.description']),
            new Affix(['key' => Affix::AFFIX_RAGING, 'name' => 'affixes.raging.name', 'icon_file_id' => -1, 'affix_id' => 6, 'description' => 'affixes.raging.description']),
            new Affix(['key' => Affix::AFFIX_RELENTLESS, 'name' => 'affixes.relentless.name', 'icon_file_id' => -1, 'affix_id' => 1, 'description' => 'affixes.relentless.description']),
            new Affix(['key' => Affix::AFFIX_SANGUINE, 'name' => 'affixes.sanguine.name', 'icon_file_id' => -1, 'affix_id' => 8, 'description' => 'affixes.sanguine.description']),
            new Affix(['key' => Affix::AFFIX_SKITTISH, 'name' => 'affixes.skittish.name', 'icon_file_id' => -1, 'affix_id' => 2, 'description' => 'affixes.skittish.description']),
            new Affix(['key' => Affix::AFFIX_TEEMING, 'name' => 'affixes.teeming.name', 'icon_file_id' => -1, 'affix_id' => 5, 'description' => 'affixes.teeming.description']),
            new Affix(['key' => Affix::AFFIX_TYRANNICAL, 'name' => 'affixes.tyrannical.name', 'icon_file_id' => -1, 'affix_id' => 9, 'description' => 'affixes.tyrannical.description']),
            new Affix(['key' => Affix::AFFIX_VOLCANIC, 'name' => 'affixes.volcanic.name', 'icon_file_id' => -1, 'affix_id' => 3, 'description' => 'affixes.volcanic.description']),

            new Affix(['key' => Affix::AFFIX_REAPING, 'name' => 'affixes.reaping.name', 'icon_file_id' => -1, 'affix_id' => 117, 'description' => 'affixes.reaping.description']),
            new Affix(['key' => Affix::AFFIX_BEGUILING, 'name' => 'affixes.beguiling.name', 'icon_file_id' => -1, 'affix_id' => 119, 'description' => 'affixes.beguiling.description']),
            new Affix(['key' => Affix::AFFIX_AWAKENED, 'name' => 'affixes.awakened.name', 'icon_file_id' => -1, 'affix_id' => 120, 'description' => 'affixes.awakened.description']),

            new Affix(['key' => Affix::AFFIX_INSPIRING, 'name' => 'affixes.inspiring.name', 'icon_file_id' => -1, 'affix_id' => 122, 'description' => 'affixes.inspiring.description']),
            new Affix(['key' => Affix::AFFIX_SPITEFUL, 'name' => 'affixes.spiteful.name', 'icon_file_id' => -1, 'affix_id' => 123, 'description' => 'affixes.spiteful.description']),
            new Affix(['key' => Affix::AFFIX_STORMING, 'name' => 'affixes.storming.name', 'icon_file_id' => -1, 'affix_id' => 124, 'description' => 'affixes.storming.description']),

            new Affix(['key' => Affix::AFFIX_PRIDEFUL, 'name' => 'affixes.prideful.name', 'icon_file_id' => -1, 'affix_id' => 121, 'description' => 'affixes.prideful.description']),
            new Affix(['key' => Affix::AFFIX_TORMENTED, 'name' => 'affixes.tormented.name', 'icon_file_id' => -1, 'affix_id' => 128, 'description' => 'affixes.tormented.description']),
            new Affix(['key' => Affix::AFFIX_UNKNOWN, 'name' => 'affixes.unknown.name', 'icon_file_id' => -1, 'affix_id' => 1, 'description' => 'affixes.unknown.description']),
            new Affix(['key' => Affix::AFFIX_INFERNAL, 'name' => 'affixes.infernal.name', 'icon_file_id' => -1, 'affix_id' => 129, 'description' => 'affixes.infernal.description']),
            new Affix(['key' => Affix::AFFIX_ENCRYPTED, 'name' => 'affixes.encrypted.name', 'icon_file_id' => -1, 'affix_id' => 130, 'description' => 'affixes.encrypted.description']),
            new Affix(['key' => Affix::AFFIX_SHROUDED, 'name' => 'affixes.shrouded.name', 'icon_file_id' => -1, 'affix_id' => 131, 'description' => 'affixes.shrouded.description']),
            new Affix(['key' => Affix::AFFIX_THUNDERING, 'name' => 'affixes.thundering.name', 'icon_file_id' => -1, 'affix_id' => 132, 'description' => 'affixes.thundering.description']),

            new Affix(['key' => Affix::AFFIX_AFFLICTED, 'name' => 'affixes.afflicted.name', 'icon_file_id' => -1, 'affix_id' => 135, 'description' => 'affixes.afflicted.description']),
            new Affix(['key' => Affix::AFFIX_ENTANGLING, 'name' => 'affixes.entangling.name', 'icon_file_id' => -1, 'affix_id' => 134, 'description' => 'affixes.entangling.description']),
            new Affix(['key' => Affix::AFFIX_INCORPOREAL, 'name' => 'affixes.incorporeal.name', 'icon_file_id' => -1, 'affix_id' => 136, 'description' => 'affixes.incorporeal.description']),

            new Affix(['key' => Affix::AFFIX_XALATATHS_BARGAIN_ASCENDANT, 'name' => 'affixes.xalataths_bargain_ascendant.name', 'icon_file_id' => -1, 'affix_id' => 148, 'description' => 'affixes.xalataths_bargain_ascendant.description']),
            new Affix(['key' => Affix::AFFIX_XALATATHS_BARGAIN_DEVOUR, 'name' => 'affixes.xalataths_bargain_devour.name', 'icon_file_id' => -1, 'affix_id' => 160, 'description' => 'affixes.xalataths_bargain_devour.description']),
            new Affix(['key' => Affix::AFFIX_XALATATHS_BARGAIN_VOIDBOUND, 'name' => 'affixes.xalataths_bargain_voidbound.name', 'icon_file_id' => -1, 'affix_id' => 158, 'description' => 'affixes.xalataths_bargain_voidbound.description']),
            new Affix(['key' => Affix::AFFIX_XALATATHS_BARGAIN_OBLIVION, 'name' => 'affixes.xalataths_bargain_oblivion.name', 'icon_file_id' => -1, 'affix_id' => 159, 'description' => 'affixes.xalataths_bargain_oblivion.description']),
            new Affix(['key' => Affix::AFFIX_XALATATHS_BARGAIN_FRENZIED, 'name' => 'affixes.xalataths_bargain_frenzied.name', 'icon_file_id' => -1, 'affix_id' => 153, 'description' => 'affixes.xalataths_bargain_frenzied.description']),
            new Affix(['key' => Affix::AFFIX_XALATATHS_GUILE, 'name' => 'affixes.xalataths_guile.name', 'icon_file_id' => -1, 'affix_id' => 147, 'description' => 'affixes.xalataths_guile.description']),
            new Affix(['key' => Affix::AFFIX_CHALLENGERS_PERIL, 'name' => 'affixes.challengers_peril.name', 'icon_file_id' => -1, 'affix_id' => 152, 'description' => 'affixes.challengers_peril.description']),

        ]);

        foreach ($affixes as $affix) {
            /** @var Affix $affix */
            $affix->setTable(DatabaseSeeder::getTempTableName(Affix::class))->save();

            $iconName          = $affix->image_url;
            $icon              = new File();
            $icon->model_id    = $affix->id;
            $icon->model_class = get_class($affix);
            $icon->disk        = 'public';
            $icon->path        = sprintf('images/affixes/%s.jpg', $iconName);
            $icon->save();

            $affix->icon_file_id = $icon->id;
            $affix->setTable(DatabaseSeeder::getTempTableName(Affix::class))->save();
        }

        /** @var Collection<Expansion> $expansions */
        $expansions = Expansion::all()->mapWithKeys(function (Expansion $expansion) {
            return [$expansion->shortname => $expansion->id];
        });

        $legion       = $expansions->get(Expansion::EXPANSION_LEGION);
        $bfa          = $expansions->get(Expansion::EXPANSION_BFA);
        $shadowlands  = $expansions->get(Expansion::EXPANSION_SHADOWLANDS);
        $dragonflight = $expansions->get(Expansion::EXPANSION_DRAGONFLIGHT);
        $tww          = $expansions->get(Expansion::EXPANSION_TWW);

        $season1  = ['season_id' => Season::SEASON_BFA_S1, 'expansion_id' => $bfa, 'key_levels' => [2, 4, 7, 10]];
        $season2  = ['season_id' => Season::SEASON_BFA_S2, 'expansion_id' => $bfa, 'key_levels' => [2, 4, 7, 10]];
        $season3  = ['season_id' => Season::SEASON_BFA_S3, 'expansion_id' => $bfa, 'key_levels' => [2, 4, 7, 10]];
        $season4  = ['season_id' => Season::SEASON_BFA_S4, 'expansion_id' => $bfa, 'key_levels' => [2, 4, 7, 10]];
        $season5  = ['season_id' => Season::SEASON_SL_S1, 'expansion_id' => $shadowlands, 'key_levels' => [2, 4, 7, 10]];
        $season6  = ['season_id' => Season::SEASON_SL_S2, 'expansion_id' => $shadowlands, 'key_levels' => [2, 4, 7, 10]];
        $season7  = ['season_id' => Season::SEASON_LEGION_TW_S1, 'expansion_id' => $legion, 'key_levels' => [2, 4, 7, 10]];
        $season8  = ['season_id' => Season::SEASON_SL_S3, 'expansion_id' => $shadowlands, 'key_levels' => [2, 4, 7, 10]];
        $season9  = ['season_id' => Season::SEASON_SL_S4, 'expansion_id' => $shadowlands, 'key_levels' => [2, 4, 7, 10]];
        $season10 = ['season_id' => Season::SEASON_DF_S1, 'expansion_id' => $dragonflight, 'key_levels' => [2, 4, 7, 10]];
        $season11 = ['season_id' => Season::SEASON_DF_S2, 'expansion_id' => $dragonflight, 'key_levels' => [2, 7, 14]];
        $season12 = ['season_id' => Season::SEASON_DF_S3, 'expansion_id' => $dragonflight, 'key_levels' => [2, 7, 14]];
        $season13 = ['season_id' => Season::SEASON_DF_S4, 'expansion_id' => $dragonflight, 'key_levels' => [2, 7, 14]];
        $season14 = ['season_id' => Season::SEASON_TWW_S1, 'expansion_id' => $tww, 'key_levels' => [2, 4, 7, 10, 12]];

        $groups = [
            array_merge($season1, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_NECROTIC, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_SKITTISH, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_QUAKING, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_NECROTIC, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_SKITTISH, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_TEEMING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_QUAKING, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_INFESTED]]),
            array_merge($season1, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_INFESTED]]),

            array_merge($season2, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_NECROTIC, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_SKITTISH, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_QUAKING, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_NECROTIC, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_SKITTISH, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_TEEMING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_QUAKING, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_NECROTIC, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_SKITTISH, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_TEEMING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_REAPING]]),
            array_merge($season2, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_REAPING]]),

            array_merge($season3, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_SKITTISH, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_NECROTIC, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_QUAKING, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_NECROTIC, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_QUAKING, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_SKITTISH, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 2, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_BEGUILING]]),
            array_merge($season3, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_TEEMING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_BEGUILING]]),

            array_merge($season4, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_SKITTISH, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_NECROTIC, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_QUAKING, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_NECROTIC, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TEEMING, Affix::AFFIX_QUAKING, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_SKITTISH, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_AWAKENED]]),
            array_merge($season4, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_TEEMING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_AWAKENED]]),

            array_merge($season5, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SPITEFUL, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INSPIRING, Affix::AFFIX_NECROTIC, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_QUAKING, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SPITEFUL, Affix::AFFIX_VOLCANIC, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_NECROTIC, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INSPIRING, Affix::AFFIX_STORMING, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_QUAKING, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_PRIDEFUL]]),
            array_merge($season5, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_STORMING, Affix::AFFIX_PRIDEFUL]]),

            array_merge($season6, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INSPIRING, Affix::AFFIX_QUAKING, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_NECROTIC, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_STORMING, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INSPIRING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SPITEFUL, Affix::AFFIX_NECROTIC, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_QUAKING, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SANGUINE, Affix::AFFIX_STORMING, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_RAGING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 1, 'affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_TORMENTED]]),
            array_merge($season6, ['seasonal_index' => 0, 'affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SPITEFUL, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_TORMENTED]]),

            array_merge($season7, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_INFERNAL]]),
            array_merge($season7, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_QUAKING, Affix::AFFIX_INFERNAL]]),

            array_merge($season8, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INSPIRING, Affix::AFFIX_QUAKING, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_STORMING, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INSPIRING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SPITEFUL, Affix::AFFIX_NECROTIC, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_QUAKING, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SANGUINE, Affix::AFFIX_STORMING, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_RAGING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_ENCRYPTED]]),
            array_merge($season8, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SPITEFUL, Affix::AFFIX_NECROTIC, Affix::AFFIX_ENCRYPTED]]),

            array_merge($season9, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INSPIRING, Affix::AFFIX_QUAKING, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_STORMING, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INSPIRING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SPITEFUL, Affix::AFFIX_NECROTIC, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_QUAKING, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SANGUINE, Affix::AFFIX_STORMING, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_RAGING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SHROUDED]]),
            array_merge($season9, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SPITEFUL, Affix::AFFIX_NECROTIC, Affix::AFFIX_SHROUDED]]),

            array_merge($season10, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SPITEFUL, Affix::AFFIX_QUAKING, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BURSTING, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BOLSTERING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_RAGING, Affix::AFFIX_QUAKING, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SANGUINE, Affix::AFFIX_VOLCANIC, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_RAGING, Affix::AFFIX_STORMING, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_SPITEFUL, Affix::AFFIX_GRIEVOUS, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_SANGUINE, Affix::AFFIX_EXPLOSIVE, Affix::AFFIX_THUNDERING]]),
            array_merge($season10, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_BOLSTERING, Affix::AFFIX_STORMING, Affix::AFFIX_THUNDERING]]),

            array_merge($season11, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_AFFLICTED, Affix::AFFIX_BOLSTERING]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INCORPOREAL, Affix::AFFIX_SANGUINE]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BURSTING]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SPITEFUL]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_STORMING, Affix::AFFIX_RAGING]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BOLSTERING]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INCORPOREAL, Affix::AFFIX_SPITEFUL]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_AFFLICTED, Affix::AFFIX_RAGING]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SANGUINE]]),
            array_merge($season11, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING]]),

            array_merge($season12, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_AFFLICTED, Affix::AFFIX_BOLSTERING]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INCORPOREAL, Affix::AFFIX_SANGUINE]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BURSTING]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SPITEFUL]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_STORMING, Affix::AFFIX_RAGING]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BOLSTERING]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INCORPOREAL, Affix::AFFIX_SPITEFUL]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_AFFLICTED, Affix::AFFIX_RAGING]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SANGUINE]]),
            array_merge($season12, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING]]),

            array_merge($season13, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_AFFLICTED, Affix::AFFIX_BOLSTERING]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_INCORPOREAL, Affix::AFFIX_SANGUINE]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BURSTING]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SPITEFUL]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_STORMING, Affix::AFFIX_RAGING]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BOLSTERING]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_INCORPOREAL, Affix::AFFIX_SPITEFUL]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_AFFLICTED, Affix::AFFIX_RAGING]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_VOLCANIC, Affix::AFFIX_SANGUINE]]),
            array_merge($season13, ['affixes' => [Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING]]),

            // New season: remember to rearrange the affixes! $season->start_affix_group_index is not taken into account when calculating
            // affixes overview. You need to offset the affixes yourself by this number. Fiddle around with it - the current week needs to match
            // the affix overview page's current week!
            array_merge($season14, ['affixes' => [Affix::AFFIX_XALATATHS_BARGAIN_ASCENDANT, Affix::AFFIX_FORTIFIED, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_UNKNOWN, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_FORTIFIED, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_UNKNOWN, Affix::AFFIX_FORTIFIED, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_XALATATHS_BARGAIN_ASCENDANT, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_FORTIFIED, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_XALATATHS_BARGAIN_OBLIVION, Affix::AFFIX_FORTIFIED, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_XALATATHS_BARGAIN_VOIDBOUND, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_FORTIFIED, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_XALATATHS_BARGAIN_DEVOUR, Affix::AFFIX_FORTIFIED, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_XALATATHS_GUILE]]),
            array_merge($season14, ['affixes' => [Affix::AFFIX_XALATATHS_BARGAIN_OBLIVION, Affix::AFFIX_TYRANNICAL, Affix::AFFIX_CHALLENGERS_PERIL, Affix::AFFIX_FORTIFIED, Affix::AFFIX_XALATATHS_GUILE]]),
        ];
        // @formatter:on

        $affixGroupAttributes          = [];
        $affixGroupCouplingsAttributes = [];
        $affixGroupId                  = 1;
        foreach ($groups as $groupArr) {
            $keyLevels = $groupArr['key_levels'];
            unset($groupArr['key_levels']);

            $affixGroupAttributes[] = [
                'season_id'      => $groupArr['season_id'],
                'expansion_id'   => $groupArr['expansion_id'],
                'seasonal_index' => $groupArr['seasonal_index'] ?? null,
                'confirmed'      => $groupArr['confirmed'] ?? true,
            ];

            $i = 0;
            foreach ($groupArr['affixes'] as $affixName) {
                $affix = $this->findAffix($affixes, $affixName);

                $affixGroupCouplingsAttributes[] = [
                    'affix_id'       => $affix?->id,
                    'affix_group_id' => $affixGroupId,
                    'key_level'      => $keyLevels[$i],
                ];
                $i++;
            }

            $affixGroupId++;
        }

        AffixGroup::from(DatabaseSeeder::getTempTableName(AffixGroup::class))->insert($affixGroupAttributes);
        AffixGroupCoupling::from(DatabaseSeeder::getTempTableName(AffixGroupCoupling::class))->insert($affixGroupCouplingsAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            Affix::class,
            AffixGroup::class,
            AffixGroupCoupling::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
