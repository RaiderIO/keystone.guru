<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JsonStringCountRule implements ValidationRule
{
    public function __construct(private readonly int $count)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $decoded = json_decode((string)$value, true);

        if (!is_array($decoded) || count($decoded) < $this->count) {
            $fail(__('rules.json_string_count_rule.message', ['count' => $this->count]));
        }
    }
}
