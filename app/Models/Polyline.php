<?php

namespace App\Models;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int         $id
 * @property int         $model_id
 * @property string      $model_class
 * @property string      $color
 * @property string|null $color_animated
 * @property int         $weight
 * @property string      $vertices_json JSON encoded vertices
 *
 * @property Model       $model
 *
 * @mixin Eloquent
 */
class Polyline extends Model implements MappingModelCloneableInterface
{
    use HasGenericModelRelation;

    public $timestamps = false;
    public $visible    = ['color', 'color_animated', 'weight', 'vertices_json'];
    public $fillable   = ['id', 'model_id', 'model_class', 'color', 'color_animated', 'weight', 'vertices_json'];

    /**
     * @return Collection|LatLng[]
     */
    public function getDecodedLatLngs(?Floor $floor = null): Collection
    {
        $result = collect();

        $decoded = json_decode($this->vertices_json, true);

        if (is_array($decoded)) {
            foreach ($decoded as $latLng) {
                $result->push(new LatLng($latLng['lat'], $latLng['lng'], $floor));
            }
        }

        return $result;
    }

    /**
     * @param MappingVersion             $mappingVersion
     * @param MappingModelInterface|null $newParent
     *
     * @return Polyline
     */
    public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Polyline
    {
        /** @var Polyline|MappingModelInterface $clone */
        $clone           = clone $this;
        $clone->exists   = false;
        $clone->id       = null;
        $clone->model_id = $newParent->id;
        $clone->save();

        return $clone;
    }
}
