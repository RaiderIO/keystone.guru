<?php

namespace App\Models\Traits;

trait BitMasks
{
    /**
     * @param int $value
     * @param int $flag
     * @return int
     */
    protected function bitMaskAdd(int $value, int $flag): int
    {
        return $value | $flag;
    }

    /**
     * @param int $value
     * @param int $flag
     * @return int
     */
    protected function bitMaskRemove(int $value, int $flag): int
    {
        return $value & ~$flag;
    }

    /**
     * @param int $value
     * @param int $flag
     * @return bool
     */
    protected function bitMaskHasValue(int $value, int $flag): bool
    {
        return ($value & $flag) > 0;
    }
}
