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
use App\Models\Interfaces\HasPolylineInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait SavesPolylines
{
    use ChangesDungeonRoute;

    /** The decimals Leaflet's toGeoJSON() sends a vertex with. */
    private const int VERTEX_PRECISION = 6;

    /**
     * @param  Floor|null                                                                        $facadeFloor The facade floor the vertices were drawn on, if any
     * @param  array{color: string, color_animated: string, weight?: int, vertices_json: string} $data
     * @throws Exception
     * @throws ValidationException
     */
    private function savePolylineToModel(
        CoordinatesServiceInterface $coordinatesService,
        ?DungeonRoute               $dungeonRoute,
        ?MappingVersion             $mappingVersion,
        ?Floor                      $facadeFloor,
        Polyline                    $polyline,
        HasPolylineInterface&Model  $ownerModel,
        array                       $data,
    ): Polyline {
        $beforePolyline = clone $polyline;
        $changedFloor   = null;

        // Vertices drawn on the facade floor must be converted back onto the floors they belong to
        if ($facadeFloor !== null && $mappingVersion !== null) {
            $vertices     = json_decode($data['vertices_json'], true);
            $realVertices = [];
            foreach ($vertices as $vertex) {
                $latLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
                    $mappingVersion,
                    new LatLng($vertex['lat'], $vertex['lng'], $facadeFloor),
                    $changedFloor,
                );

                $realVertices[] = [
                    'lat' => $latLng->getLat(self::VERTEX_PRECISION),
                    'lng' => $latLng->getLng(self::VERTEX_PRECISION),
                ];
                // Assume the floor of the first vertex in the list
                if ($changedFloor === null) {
                    $changedFloor = $latLng->getFloor();
                }
            }

            $data['vertices_json'] = json_encode($realVertices);

            // The submitted vertices were validated against this limit, but their real-floor equivalents can be longer
            if (strlen($data['vertices_json']) > Polyline::VERTICES_JSON_MAX_LENGTH) {
                throw ValidationException::withMessages([
                    'polyline.vertices_json' => __('validation.max.string', [
                        'attribute' => 'polyline.vertices_json',
                        'max'       => Polyline::VERTICES_JSON_MAX_LENGTH,
                    ]),
                ]);
            }
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
            'floor_id'    => $changedFloor === null ? $ownerModel->getAttribute('floor_id') : $changedFloor->id,
        ]);
        $ownerModel->setRelation('polyline', $polyline);

        if ($changedFloor !== null) {
            $ownerModel->setRelation('floor', $changedFloor);
        }

        return $polyline;
    }
}
