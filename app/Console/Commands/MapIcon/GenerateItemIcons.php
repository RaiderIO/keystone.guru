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
            'assets/images/mapicon_gen/spell_animabastion_orb.jpg'           => 'nw_item_anima.png',
            'assets/images/mapicon_gen/171750.png'                           => 'nw_item_goliath.png',
            'assets/images/mapicon_gen/inv_mace_1h_bastionquest_b_01.png'    => 'nw_item_hammer.png',
            'assets/images/mapicon_gen/inv_shield_1h_bastionquest_b_01.jpg'  => 'nw_item_shield.png',
            'assets/images/mapicon_gen/inv_polearm_2h_bastionquest_b_01.png' => 'nw_item_spear.png',
            'assets/images/mapicon_gen/inv_misc_starspecklemushroom.jpg'     => 'mists_item_statshroom.png',
            'assets/images/mapicon_gen/inv_mushroom_06.jpg'                  => 'mists_item_toughshroom.png',
            'assets/images/mapicon_gen/inv_offhand_1h_nerubianraid_d_01.jpg' => 'cot_item_shadecaster.png',
        ];


        foreach ($imagePaths as $sourceImage => $targetImage) {
            $imageService->convertToItemImage(
                resource_path($sourceImage),
                resource_path(sprintf('assets/images/mapicon/%s', $targetImage))
            );
        }

        return 0;
    }
}
