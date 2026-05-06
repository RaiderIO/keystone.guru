<?php

namespace App\Console\Commands\MapIcon;

use App\Service\Image\ImageServiceInterface;
use Illuminate\Console\Command;

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
