<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 17:51
 */

namespace App\Http\Controllers\Traits;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait SavesPolylines
{
    use ChangesDungeonRoute;

    /**
     * @param  array{color: string, color_animated: string, weight: int, vertices_json: string} $data
     * @throws Exception
     */
    private function savePolylineToModel(
        CoordinatesServiceInterface $coordinatesService,
        ?DungeonRoute               $dungeonRoute,
        MappingVersion              $mappingVersion,
        Polyline                    $polyline,
        ?Model                      $beforeModel,
        Model                       $ownerModel,
        array                       $data,
    ): Polyline {
        $beforePolyline = clone $polyline;

        // The incoming lat/lngs are facade lat/lngs, save the icon on the proper floor
        $useFacade        = $mappingVersion->facade_enabled && User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
        $originalVertices = $data['vertices_json'];
        /** @var Floor|null $originalFloor */
        $originalFloor = $ownerModel->getAttribute('floor');
        $changedFloor  = null;

        if ($useFacade) {
            $vertices     = json_decode($data['vertices_json'], true);
            $realVertices = [];
            foreach ($vertices as $vertex) {
                /** @var Floor|null $ownerFloor */
                $ownerFloor = $ownerModel->getAttribute('floor');
                $latLng     = $coordinatesService->convertFacadeMapLocationToMapLocation(
                    $mappingVersion,
                    new LatLng($vertex['lat'], $vertex['lng'], $ownerFloor),
                    $changedFloor,
                );

                $realVertices[] = $latLng->toArray();
                // Assume the floor of the first vertex in the list
                if ($changedFloor === null) {
                    $changedFloor = $latLng->getFloor();
                }
            }

            $data['vertices_json'] = json_encode($realVertices);
        }

        $polyline = Polyline::updateOrCreate([
            'id' => $polyline->id,
        ], [
            'model_id'       => $ownerModel->getKey(),
            'model_class'    => $ownerModel::class,
            'color'          => $data['color'],
            'color_animated' => Auth::check() &&
                Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES) ?
                    $data['color_animated'] : null,
            'weight'        => (int)($data['weight'] ?? 3),
            'vertices_json' => $data['vertices_json'],
        ]);

        if ($dungeonRoute !== null) {
            $this->dungeonRouteChanged($dungeonRoute, $beforePolyline->exists ? $beforePolyline : null, $polyline);
        }

        // Couple the model to the newly created/updated polyline
        $ownerModel->update([
            'polyline_id' => $polyline->id,
            'floor_id'    => $changedFloor !== null ? $changedFloor->id : $originalFloor->id,
        ]);
        $ownerModel->setRelation('polyline', $polyline);

        if ($dungeonRoute !== null) {
            $this->dungeonRouteChanged($dungeonRoute, $beforeModel, $ownerModel);
        }

        // If we received a request from facade, we need to convert the vertices back to facade coordinates
        if ($useFacade) {
            $ownerModel->setRelation('floor', $originalFloor);
            $ownerModel->setAttribute('floor_id', $originalFloor->id);
            $polyline->setAttribute('vertices_json', $originalVertices);
        }

        return $polyline;
    }
}
