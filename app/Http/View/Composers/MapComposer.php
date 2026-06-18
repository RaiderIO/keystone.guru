<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

class MapComposer
{
    public function compose(View $view): void
    {
        $view->with('assetsBaseUrl', config('keystoneguru.assets_base_url'));
        $view->with('tilesBaseUrl', config('keystoneguru.tiles_base_url'));
    }
}
