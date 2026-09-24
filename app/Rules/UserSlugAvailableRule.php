<?php

namespace App\Rules;

use App\Service\User\UserSlugServiceInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a username whose profile URL is already another user's: `woe2 1234` and `woe2#1234` are
 * different names but both reduce to /user/woe2-1234.
 */
class UserSlugAvailableRule implements ValidationRule
{
    public function __construct(
        public ?int $exceptUserId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        if (app(UserSlugServiceInterface::class)->isBaseSlugTaken($value, $this->exceptUserId)) {
            $fail(__('rules.user_slug_available_rule.taken'));
        }
    }
}
