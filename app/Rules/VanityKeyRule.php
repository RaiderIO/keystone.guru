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
     * @param class-string<Model> $modelClass        The model the key is stored on.
     * @param Model|null          $ignoreModel       The model being saved, whose own keys do not count as taken.
     * @param array<int, string>  $reservedKeys      Keys shadowed by a static route, which would never resolve.
     * @param bool                $publicKeyIsPrefix True when the model's route key is `<public key>-<slug>`, so
     *                                               everything up to the first dash is a public key as well.
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly ?Model $ignoreModel = null,
        private readonly array  $reservedKeys = [],
        private readonly bool   $publicKeyIsPrefix = false,
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

        if ($this->isTaken('vanity_key', $value)) {
            $fail(__('rules.vanity_key_rule.taken'));

            return;
        }

        // Both the whole key and, where the route key carries a slug behind it, its first segment: either
        // form is a URL that already points at another row
        $publicKeys = $this->publicKeyIsPrefix ? [$value, explode('-', $value, 2)[0]] : [$value];

        foreach (array_unique($publicKeys) as $publicKey) {
            if ($this->isTaken('public_key', $publicKey)) {
                $fail(__('rules.vanity_key_rule.taken'));

                return;
            }
        }
    }

    /**
     * A key already held by another row of the same table is taken: the vanity key wins in route binding,
     * so re-using another row's public key would take over that row's URL.
     */
    private function isTaken(string $column, string $value): bool
    {
        return $this->modelClass::query()
            ->where($column, $value)
            ->when($this->ignoreModel?->exists, fn($builder) => $builder->whereKeyNot($this->ignoreModel->getKey()))
            ->exists();
    }
}
