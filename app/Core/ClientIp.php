<?php

declare(strict_types=1);

namespace Garage\Core;

/**
 * Real visitor IP and scheme behind Cloudflare.
 *
 * REMOTE_ADDR is the Cloudflare edge, shared by many visitors. CF-Connecting-IP
 * (and the forwarded scheme) are trusted ONLY when the connection actually comes
 * from an official Cloudflare range; otherwise anyone could send those headers
 * and dodge the rate limit.
 */
final class ClientIp
{
    /** https://www.cloudflare.com/ips-v4 and /ips-v6 (fetched 2026-09-26). */
    private const CLOUDFLARE_RANGES = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    public static function resolve(array $server): string
    {
        $remote = (string) ($server['REMOTE_ADDR'] ?? '0.0.0.0');
        $cfIp = (string) ($server['HTTP_CF_CONNECTING_IP'] ?? '');

        if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP) && self::isCloudflare($remote)) {
            return $cfIp;
        }

        return $remote;
    }

    public static function isSecure(array $server): bool
    {
        if (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off') {
            return true;
        }
        if (!self::isCloudflare((string) ($server['REMOTE_ADDR'] ?? ''))) {
            return false;
        }
        if (strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }
        $visitor = json_decode((string) ($server['HTTP_CF_VISITOR'] ?? ''), true);

        return is_array($visitor) && ($visitor['scheme'] ?? '') === 'https';
    }

    public static function isCloudflare(string $ip): bool
    {
        foreach (self::CLOUDFLARE_RANGES as $range) {
            if (self::inCidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /** Masked IP for logs: 203.0.113.x / 2001:db8:85a3::x */
    public static function mask(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.x', $ip) ?? 'invalid';
        }
        $bin = @inet_pton($ip);
        if ($bin === false) {
            return 'invalid';
        }

        return inet_ntop(substr($bin, 0, 6) . str_repeat("\0", 10)) . 'x';
    }

    /** Works for IPv4 and IPv6: compares inet_pton bytes; mixed families never match. */
    public static function inCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bits = (int) $bits;
        $fullBytes = intdiv($bits, 8);
        if (substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        $remBits = $bits % 8;
        if ($remBits === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $remBits)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }
}
