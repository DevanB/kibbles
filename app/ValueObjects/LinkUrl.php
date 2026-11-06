<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Exceptions\InvalidLinkUrl;
use App\Services\PublicIpResolver;

final readonly class LinkUrl
{
    /**
     * @var list<string>
     */
    private const array CLICK_IDS = [
        'fbclid',
        'gclid',
        'gclsrc',
        'dclid',
        'gbraid',
        'wbraid',
        'msclkid',
        'twclid',
        'ttclid',
        'li_fat_id',
        'igshid',
        'mc_eid',
        'yclid',
        '_hsenc',
        '_hsmi',
        'mkt_tok',
        'ndclid',
    ];

    /**
     * @var list<string>
     */
    private const array LOCAL_HOSTS = [
        'localhost',
    ];

    /**
     * @var list<string>
     */
    private const array LOCAL_SUFFIXES = [
        '.local',
        '.localhost',
        '.internal',
        '.lan',
        '.home',
        '.corp',
    ];

    private function __construct(
        public string $value,
        public string $host,
        public bool $fetchable,
    ) {}

    public static function parse(string $raw): self
    {
        $trimmed = mb_trim($raw);

        if ($trimmed === '') {
            throw InvalidLinkUrl::make();
        }

        $parts = parse_url($trimmed);

        if ($parts === false) {
            throw InvalidLinkUrl::make();
        }

        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));

        if ($scheme !== 'http' && $scheme !== 'https') {
            throw InvalidLinkUrl::make();
        }

        $host = self::normalizedHost((string) ($parts['host'] ?? ''));

        if ($host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw InvalidLinkUrl::make();
        }

        $path = $parts['path'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = self::cleanedQuery($parts['query'] ?? null);
        $value = $scheme.'://'.self::hostForUrl($host).$port.$path;

        if ($query !== '') {
            $value .= '?'.$query;
        }

        return new self($value, $host, self::isFetchable($host));
    }

    public static function isValid(string $raw): bool
    {
        try {
            self::parse($raw);

            return true;
        } catch (InvalidLinkUrl) {
            return false;
        }
    }

    private static function cleanedQuery(?string $query): string
    {
        if ($query === null || $query === '') {
            return '';
        }

        $kept = [];

        foreach (explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }

            $name = mb_strtolower((string) mb_strstr($pair, '=', true) ?: $pair);

            if (str_starts_with($name, 'utm_') || in_array($name, self::CLICK_IDS, true)) {
                continue;
            }

            $kept[] = $pair;
        }

        return implode('&', $kept);
    }

    private static function normalizedHost(string $host): string
    {
        $host = mb_strtolower($host);

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return mb_substr($host, 1, -1);
        }

        return $host;
    }

    private static function hostForUrl(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return '['.$host.']';
        }

        return $host;
    }

    private static function isFetchable(string $host): bool
    {
        if (in_array($host, self::LOCAL_HOSTS, true)) {
            return false;
        }

        foreach (self::LOCAL_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return PublicIpResolver::isPublic($host);
        }

        if (ctype_digit($host)) {
            return PublicIpResolver::isPublic(long2ip((int) $host));
        }

        return true;
    }
}
