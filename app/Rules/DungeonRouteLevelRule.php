<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DungeonRouteLevelRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function passes(string $attribute, $value): bool
    {
        $explode = explode(';', (string)$value);

        return count($explode) === 2 && is_numeric($explode[0]) && is_numeric($explode[1]);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('rules.dungeon_route_level_rule.message');
    }
}
