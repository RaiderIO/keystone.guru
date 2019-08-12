<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 12-10-2018
 * Time: 10:33
 */

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ChecksForDuplicates
{
    /**
     * @param Model $candidate
     * @param array $fields Additional fields to check on for duplicates.
     * @param bool $abort True to abort, false to return true|false upon completion.
     * @return bool False if no duplicate was found, true if there was a duplicate.
     */
    function checkForDuplicate($candidate, $fields = [], $abort = true)
    {
        // Find out of there is a duplicate
        /** @var Builder $query */
        // Round it like MySql does, otherwise we get strange rounding errors and it won't detect it as a duplicate
        /** @var \Eloquent $modelClass */
        $modelClass = get_class($candidate);
        $query = $modelClass::where('lat', round($candidate->lat, 2, PHP_ROUND_HALF_EVEN))
            ->where('lng', round($candidate->lng, 2, PHP_ROUND_HALF_EVEN));

        $fields[] = 'dungeon_route_id';

        foreach ($fields as $field) {
            if( isset($candidate[$field]) ){
                $query->where($field, $candidate->$field);
            }
        }

        $count = $query->get()->count();

        // Assume duplicate
        $result = true;
        if ($count > 0) {
            if ($abort) {
                $this->abortDuplicate();
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Abort because a duplicate has been found.
     */
    function abortDuplicate()
    {
        abort(\Teapot\StatusCode\Http::BAD_REQUEST, 'This object already exists. Please refresh the page.');
    }

    /**
     * Checks a list of vertices for duplicates.
     * @param $className
     * @param $verticesArray
     */
    function checkForDuplicateVertices($className, $verticesArray)
    {
        // Store them
        $failures = 0;
        foreach ($verticesArray as $key => $vertex) {
            // Check if there was a duplicate
            $failures += $this->checkForDuplicate(new $className($verticesArray[$key]), [], false) ? 1 : 0;
        }

        // Only if all nodes are marked as duplicates do we fail the request
        if ($failures === count($verticesArray)) {
            $this->abortDuplicate();
        }
    }
}