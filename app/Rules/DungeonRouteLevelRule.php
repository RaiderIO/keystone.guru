<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DungeonRouteLevelRule implements ValidationRule
{
    /**
     * @param string  $attribute
     * @param         $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, $value, Closure $fail): void
    {
        $explode = explode(';', (string)$value);

        if (count($explode) !== 2 || !is_numeric($explode[0]) || !is_numeric($explode[1])) {
            $fail(__('rules.dungeon_route_level_rule.message'));
        }
    }
}
