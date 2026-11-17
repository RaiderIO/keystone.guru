<?php

namespace App\Logic\Utils;

class IpAddress
{
    /**
     * An IPv6 subscriber is normally handed a whole /64, so every address inside it belongs to one visitor.
     */
    private const int IPV6_NETWORK_PREFIX_BYTES = 8;

    private const string IPV4_MAPPED_IPV6_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    /**
     * The value a rate limit counts a visitor by: an IPv4 address as it is, an IPv6 address as the /64 network it
     * belongs to (e.g. `2001:db8:1:2::/64`), and an IPv4-mapped IPv6 address as the IPv4 address it maps.
     * Anything that is not an IP address is returned unchanged.
     */
    public static function rateLimitKey(?string $ipAddress): string
    {
        if ($ipAddress === null || filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return (string)$ipAddress;
        }

        $packed = (string)inet_pton($ipAddress);

        if (str_starts_with($packed, self::IPV4_MAPPED_IPV6_PREFIX)) {
            return (string)inet_ntop(substr($packed, strlen(self::IPV4_MAPPED_IPV6_PREFIX)));
        }

        $network = str_pad(substr($packed, 0, self::IPV6_NETWORK_PREFIX_BYTES), strlen($packed), "\0");

        return sprintf('%s/%d', inet_ntop($network), self::IPV6_NETWORK_PREFIX_BYTES * 8);
    }
}
