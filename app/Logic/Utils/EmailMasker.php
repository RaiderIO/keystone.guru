<?php

namespace App\Logic\Utils;

/**
 * Reduces an email address to something recognisable but not usable.
 *
 * The Patreon diagnostics endpoints (#4373) are open to the `ai_agent` role as well as admins, so that
 * the tooling can be driven from a local dev machine without a production admin account. Patron emails
 * are the one genuinely sensitive thing those endpoints touch, and nothing about diagnosing a sync needs
 * the actual address - only "is this the same address as the one I asked about". Masking is applied on
 * the way out; the endpoints never return an unmasked address to anyone.
 */
class EmailMasker
{
    /**
     * `someone@example.com` becomes `s*****e@e*****e.com` - enough to compare two addresses at a glance
     * and to spot a domain change, not enough to contact anyone.
     */
    public static function mask(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        $atPosition = mb_strrpos($email, '@');
        if ($atPosition === false) {
            return self::maskPart($email);
        }

        $localPart = mb_substr($email, 0, $atPosition);
        $domain    = mb_substr($email, $atPosition + 1);

        // The TLD is kept intact - it carries no identity on its own and makes the mask readable
        $lastDot      = mb_strrpos($domain, '.');
        $maskedDomain = $lastDot === false
            ? self::maskPart($domain)
            : sprintf('%s%s', self::maskPart(mb_substr($domain, 0, $lastDot)), mb_substr($domain, $lastDot));

        return sprintf('%s@%s', self::maskPart($localPart), $maskedDomain);
    }

    /** Keeps the first and last character, replacing everything between them with a fixed-width mask. */
    private static function maskPart(string $part): string
    {
        $length = mb_strlen($part);

        // Too short to keep any character without giving the whole thing away
        if ($length <= 2) {
            return str_repeat('*', max($length, 1));
        }

        return sprintf('%s%s%s', mb_substr($part, 0, 1), str_repeat('*', 5), mb_substr($part, -1));
    }
}
