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
     * @param Polyline $polyline
     * @param Model $ownerModel
     * @param array{color: string, color_animated: string, weight: int, vertices_json: string} $data
     *
     * @return Polyline
     */
    private function savePolyline(Polyline $polyline, Model $ownerModel, array $data): Polyline
    {
        return Polyline::updateOrCreate([
            'id' => $polyline->id,
        ], [
            'model_id'       => $ownerModel->id,
            'model_class'    => get_class($ownerModel),
            'color'          => $data['color'] ?? '#f00',
            'color_animated' => Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES) ? $data['color_animated'] : null,
            'weight'         => (int)$data['weight'] ?? 2,
            'vertices_json'  => $data['vertices_json'] ?? '{}',
        ]);
    }
}
