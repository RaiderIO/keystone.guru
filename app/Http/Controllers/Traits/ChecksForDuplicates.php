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
     * @param string $foreignKey Optional foreign key to check.
     * @param bool $abort True to abort, false to return true|false upon completion.
     * @return bool False if no duplicate was found, true if there was a duplicate.
     */
    function checkForDuplicate($candidate, $foreignKey = '', $abort = true)
    {
        // Find out of there is a duplicate
        /** @var Builder $query */
        // Round it like MySql does, otherwise we get strange rounding errors and it won't detect it as a duplicate
        $query = get_class($candidate)::where('lat', round($candidate->lat, 2, PHP_ROUND_HALF_EVEN))
            ->where('lng', round($candidate->lng, 2, PHP_ROUND_HALF_EVEN));

        if ($foreignKey !== '') {
            $query->where($foreignKey, $candidate[$foreignKey]);
        }
        if (isset($candidate['dungeon_route_id'])) {
            $query->where('dungeon_route_id', $candidate->dungeon_route_id);
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
            $failures += $this->checkForDuplicate(new $className($verticesArray[$key]), '', false) ? 1 : 0;
        }

        // Only if all nodes are marked as duplicates do we fail the request
        if ($failures === count($verticesArray)) {
            $this->abortDuplicate();
        }
    }
}