<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JsonStringCountRule implements ValidationRule
{
    public function __construct(
        private readonly int  $minCount,
        private readonly ?int $maxCount = null,
    ) {
    }

    /**
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $decoded = json_decode((string)$value, true);

        if (!is_array($decoded) || count($decoded) < $this->minCount) {
            $fail(__('rules.json_string_count_rule.message_min', ['min_count' => $this->minCount]));
        } elseif ($this->maxCount !== null && count($decoded) > $this->maxCount) {
            $fail(__('rules.json_string_count_rule.message_max', ['max_count' => $this->maxCount]));
        }
    }
}
