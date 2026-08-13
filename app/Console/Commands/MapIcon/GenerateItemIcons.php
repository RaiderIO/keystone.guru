<?php

namespace App\Console\Commands\MapIcon;

use App\Service\Image\ImageServiceInterface;
use Illuminate\Console\Command;

/**
 * Renders the hexagonal item map icons from the raw WoW icons in `keystone.guru.assets/images/mapicon_gen`.
 *
 * Adding one is five registrations, all of which must agree on the same key: a constant + a new id in
 * {@see \App\Models\MapIconType::ALL}, a `MapIconTypesSeeder` entry, a `lang/en_US/mapicontypes.php` string,
 * a source => target pair below, and - if MDT draws it - an entry in
 * {@see \App\Logic\MDT\Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING}. Note that adding an
 * MDT mapping for an icon that was already placed by hand duplicates it on the next reimport (#3993).
 *
 * To get the raw icon: do **not** scrape Wowhead, its pages 403 the app container. Resolve the icon file name
 * from a texture FileDataID through wago.tools' ManifestInterfaceData DB2 and download it off Wowhead's CDN -
 * `mapicon:downloadmdtitemicons` does exactly that for every MDT POI we have no icon for. This command only
 * runs from the main checkout: the assets repo is not mounted into a worktree's container.
 *
 * The full runbook lives in the `update-mdt-package` skill, which is where this is needed almost every time.
 */
class GenerateItemIcons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapicon:generateitemicons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '(Re-)generates all item icons.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        ImageServiceInterface $imageService,
    ): int {
        $imagePaths = [
            '171750.png'                            => 'nw_item_goliath.png',
            'inv_mace_1h_bastionquest_b_01.png'     => 'nw_item_hammer.png',
            'inv_shield_1h_bastionquest_b_01.jpg'   => 'nw_item_shield.png',
            'inv_polearm_2h_bastionquest_b_01.png'  => 'nw_item_spear.png',
            'inv_misc_starspecklemushroom.jpg'      => 'mists_item_statshroom.png',
            'inv_mushroom_06.jpg'                   => 'mists_item_toughshroom.png',
            'inv_misc_root_02.jpg'                  => 'mists_item_overgrown_roots.png',
            'inv_offhand_1h_nerubianraid_d_01.jpg'  => 'cot_item_shadecaster.png',
            'inv_enchanting_craftedreagent_bar.jpg' => 'sv_item_imbued_iron_energy.png',
            'inv_misc_web_02.jpg'                   => 'ara_kara_item_silk_wrap.png',
            'inv_egg_01.jpg'                        => 'karazhan_crypts_spider_nest.png',
            'spell_holy_rebuke.jpg'                 => 'priory_blessing_of_the_sacred_flame.png',
            'spell_animabastion_orb.jpg'            => 'nw_item_anima.png',
            'inv_eng_crate.jpg'                     => 'floodgate_weapons_stockpile_explosion.png',
            'spell_fire_sealoffire.jpg'             => 'gate_of_the_setting_sun_brazier.png',
            'inv_112_arcane_beam.jpg'               => 'eco_dome_al_dani_shatter_conduit.png',
            'spell_broker_nova.jpg'                 => 'eco_dome_al_dani_disruption_grenade.png',
            'inv_112_arcane_buff.jpg'               => 'eco_dome_al_dani_kareshi_surge.png',
            'inv_cooking_10_heartystew.jpg'         => 'maisara_caverns_hearty_vilebranch_stew.png',
            'inv_enchant_voidsphere.jpg'            => 'seat_of_the_triumvirate_void_infusion.png',
            'inv_bijou_silver.jpg'                  => 'algethar_academy_black_dragonflight_pledge_pin.png',
            'inv_bijou_blue.jpg'                    => 'algethar_academy_blue_dragonflight_pledge_pin.png',
            'inv_bijou_orange.jpg'                  => 'algethar_academy_bronze_dragonflight_pledge_pin.png',
            'inv_bijou_green.jpg'                   => 'algethar_academy_green_dragonflight_pledge_pin.png',
            'inv_bijou_red.jpg'                     => 'algethar_academy_red_dragonflight_pledge_pin.png',
            'spell_arcane_mindmastery.jpg'          => 'magisters_terrace_arcane_empowerment.png',
            'inv_enchanting_wod_crystal.jpg'        => 'murder_row_fel_contraband.png',
            'inv_infernalbrimstone.jpg'             => 'murder_row_felstone.png',
            'inv_egg_08.jpg'                        => 'murder_row_felwyrm_egg.png',
            'ability_boss_kilrogg_heartseeker.jpg'  => 'murder_row_heartstop_poison.png',
            'inv_weapon_rifle_40.jpg'               => 'murder_row_loaded_pistol.png',
            'ui_profession_engineering.jpg'         => 'murder_row_overload_golem.png',
            'spell_lifegivingspeed.jpg'             => 'the_blinding_vale_flourishing_stride.png',
        ];

        foreach ($imagePaths as $sourceImage => $targetImage) {
            $targetImagePath = base_path(sprintf('../keystone.guru.assets/images/mapicon/%s', $targetImage));
            if (is_writable($targetImagePath) && !file_exists($targetImagePath)) {
                // Just write something
                file_put_contents($targetImagePath, file_get_contents($sourceImage));

                // Make sure the path is absolute
                $targetImagePath = realpath($targetImagePath);
            }

            $imageService->convertToItemImage(
                realpath(base_path(sprintf('../keystone.guru.assets/images/mapicon_gen/%s', $sourceImage))),
                $targetImagePath,
            );
        }

        return 0;
    }
}
