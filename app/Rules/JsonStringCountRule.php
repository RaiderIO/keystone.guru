<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class JsonStringCountRule implements Rule
{
    public function __construct(private readonly int $count)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) && count($decoded) >= $this->count;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('rules.json_string_count_rule.message', ['count' => $this->count]);
    }
}
