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
        ImageServiceInterface $imageService
    ): int {
        $imagePaths = [
            'spell_animabastion_orb.jpg'            => 'nw_item_anima.png',
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
        ];


        foreach ($imagePaths as $sourceImage => $targetImage) {
            $imageService->convertToItemImage(
                resource_path(sprintf('assets/images/mapicon_gen/%s', $sourceImage)),
                resource_path(sprintf('assets/images/mapicon/%s', $targetImage))
            );
        }

        return 0;
    }
}
