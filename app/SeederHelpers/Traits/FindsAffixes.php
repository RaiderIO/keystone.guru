<?php

namespace App\SeederHelpers\Traits;


use App\Models\Affix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * @mixin Seeder
 */
trait FindsAffixes
{
    /**
     * Finds an affix by name in a list of affixes.
     *
     * @param Collection|Affix[] $affixes
     * @param string $affixName
     * @return bool|Affix
     */
    private function findAffix(Collection $affixes, string $affixName)
    {
        $result = false;

        foreach ($affixes as $affix) {
            if ($affix->key === $affixName) {
                $result = $affix;
                break;
            }
        }

        if (!$result) {
            $this->command->error(sprintf('Unable to find affix %s', $affixName));
        }

        return $result;
    }
}
