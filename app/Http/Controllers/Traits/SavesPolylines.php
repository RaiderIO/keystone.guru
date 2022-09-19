<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait SavesPolylines
{
    /**
     * @param $polyline Polyline
     * @param $ownerModel Model
     * @param $data array
     *
     * @return Polyline
     */
    private function _savePolyline(Polyline $polyline, Model $ownerModel, array $data): Polyline
    {
        $polyline->model_id    = $ownerModel->id;
        $polyline->model_class = get_class($ownerModel);
        $polyline->color       = $data['color'] ?? '#f00';
        // Only set the animated color if the user has paid for it
        if (Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES)) {
            $colorAnimated            = $data['color_animated'] ?? null;
            $polyline->color_animated = empty($colorAnimated) ? null : $colorAnimated;
        }
        $polyline->weight        = (int)$data['weight'] ?? 2;
        $polyline->vertices_json = $data['vertices_json'] ?? '{}';
        $polyline->save();

        return $polyline;
    }
}
