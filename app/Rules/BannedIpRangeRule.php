<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Validates that a submitted value is a well-formed IP address or CIDR range, that the range isn't
 * so broad it could ban swaths of unrelated visitors, and that it doesn't ban the requesting admin's
 * own IP - which would lock them out of the admin panel that manages bans.
 */
class BannedIpRangeRule implements ValidationRule
{
    private const int MIN_IPV4_PREFIX_LENGTH = 24;
    private const int MIN_IPV6_PREFIX_LENGTH = 64;

    public function __construct(
        /**
         * The requesting admin's own resolved IP - used to prevent self-lockout.
         */
        public ?string $requesterIp,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        [$ip, $prefixLength] = $this->parse((string)$value);

        if ($ip === null) {
            $fail(__('rules.banned_ip_range_rule.invalid'));

            return;
        }

        $isIpv6          = str_contains($ip, ':');
        $minPrefixLength = $isIpv6 ? self::MIN_IPV6_PREFIX_LENGTH : self::MIN_IPV4_PREFIX_LENGTH;

        if ($prefixLength !== null && $prefixLength < $minPrefixLength) {
            $fail(__('rules.banned_ip_range_rule.range_too_broad', ['min' => $minPrefixLength]));

            return;
        }

        if ($this->requesterIp !== null && IpUtils::checkIp($this->requesterIp, (string)$value)) {
            $fail(__('rules.banned_ip_range_rule.self_lockout'));
        }
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function parse(string $value): array
    {
        if (!str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) !== false ? [$value, null] : [null, null];
        }

        [$ip, $prefix] = explode('/', $value, 2);

        if (!ctype_digit($prefix) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return [null, null];
        }

        $prefixLength    = (int)$prefix;
        $maxPrefixLength = str_contains($ip, ':') ? 128 : 32;

        if ($prefixLength > $maxPrefixLength) {
            return [null, null];
        }

        return [$ip, $prefixLength];
    }
}
