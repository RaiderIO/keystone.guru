<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait SavesPolylines
{
    /**
     * @param array{color: string, color_animated: string, weight: int, vertices_json: string} $data
     */
    private function savePolyline(
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        Polyline                    $polyline,
        Model                       $ownerModel,
        array                       $data,
        ?Floor                      &$changedFloor
    ): Polyline {
        // The incoming lat/lngs are facade lat/lngs, save the icon on the proper floor
        if ($mappingVersion->facade_enabled && User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE) {
            $vertices     = json_decode($data['vertices_json'], true);
            $realVertices = [];
            foreach ($vertices as $vertex) {
                $latLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
                    $mappingVersion,
                    new LatLng($vertex['lat'], $vertex['lng'], $ownerModel->floor),
                    $changedFloor
                );

                $realVertices[] = $latLng->toArray();
                $changedFloor   = $latLng->getFloor();
            }

            $data['vertices_json'] = json_encode($realVertices);
        }

        return Polyline::updateOrCreate([
            'id' => $polyline->id,
        ], [
            'model_id'       => $ownerModel->id,
            'model_class'    => $ownerModel::class,
            'color'          => $data['color'] ?? '#f00',
            'color_animated' =>
                Auth::check() &&
                Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES) ?
                    $data['color_animated'] : null,
            'weight'         => (int)($data['weight'] ?? 2),
            'vertices_json'  => $data['vertices_json'] ?? '{}',
        ]);
    }
}
