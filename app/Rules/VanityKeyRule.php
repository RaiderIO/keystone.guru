<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validates a custom (vanity) URL key for a model that also carries a random public key.
 */
class VanityKeyRule implements ValidationRule
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 48;

    /**
     * @param class-string<Model> $modelClass   The model the key is stored on.
     * @param Model|null          $ignoreModel  The model being saved, whose own keys do not count as taken.
     * @param array<int, string>  $reservedKeys Keys shadowed by a static route, which would never resolve.
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly ?Model $ignoreModel = null,
        private readonly array  $reservedKeys = [],
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value) !== 1) {
            $fail(__('rules.vanity_key_rule.invalid'));

            return;
        }

        if (strlen($value) < self::MIN_LENGTH || strlen($value) > self::MAX_LENGTH) {
            $fail(__('rules.vanity_key_rule.length', ['min' => self::MIN_LENGTH, 'max' => self::MAX_LENGTH]));

            return;
        }

        if (in_array($value, $this->reservedKeys, true)) {
            $fail(__('rules.vanity_key_rule.reserved'));

            return;
        }

        if ($this->isTaken('vanity_key', $value) || $this->isTaken('public_key', $value)) {
            $fail(__('rules.vanity_key_rule.taken'));
        }
    }

    /**
     * A key already held by another row of the same table is taken: the vanity key wins in route binding,
     * so re-using another row's public key would make that row unreachable.
     */
    private function isTaken(string $column, string $value): bool
    {
        return $this->modelClass::query()
            ->where($column, $value)
            ->when($this->ignoreModel?->exists, fn($builder) => $builder->whereKeyNot($this->ignoreModel->getKey()))
            ->exists();
    }
}
