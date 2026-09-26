<?php

namespace App\Rules;

use App\Models\User;
use App\Service\User\UserSlugServiceInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a username whose profile URL is already another user's: `woe2 1234` and `woe2#1234` are
 * different names but both reduce to /user/woe2-1234.
 *
 * A user resubmitting a name that reduces to the same slug as their current one always passes: an
 * account holding a suffixed slug (`foo-bar-2`, because an older account holds `foo-bar`) keeps it.
 */
class UserSlugAvailableRule implements ValidationRule
{
    public function __construct(
        public ?User $user = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        $userSlugService = app(UserSlugServiceInterface::class);

        if ($this->user !== null && $userSlugService->generateBaseSlug($value) === $userSlugService->generateBaseSlug($this->user->name)) {
            return;
        }

        if ($userSlugService->isBaseSlugTaken($value, $this->user?->id)) {
            $fail(__('rules.user_slug_available_rule.taken'));
        }
    }
}
