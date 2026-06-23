<?php

namespace App\Repositories\Swoole\Traits;

use Illuminate\Support\Collection;

trait ClonesCollections
{
    /**
     * @template T
     * @param  Collection<int, T> $collection
     * @return Collection<int, T>
     */
    private function cloneCollection(Collection $collection): Collection
    {
        $result = collect();

        foreach ($collection as $id => $item) {
            // Cloning is required to not adjust the original Model - it'd cause it to lose its loaded relationships for some reason
            $result->put($id, clone $item);
        }

        return $result;
    }

    /**
     * @template T
     * @param  Collection<int, T> $collection
     * @return Collection<int, T>
     */
    private function copyCollection(Collection $collection): Collection
    {
        $result = collect();

        foreach ($collection as $id => $item) {
            $result->put($id, $item);
        }

        return $result;
    }
}
