<?php

namespace Javaabu\Widgets\Support;

/**
 * Links in widget settings: http(s), mailto, tel, relative paths and
 * fragments only, so a setting can't hold a javascript: URL. The React
 * components use the same check (resources/js/url.ts).
 */
class Url
{
    public const string PATTERN = '/^(https?:\/\/[^\s]+|mailto:[^\s]+|tel:[^\s]+|\/(?!\/)[^\s]*|#[^\s]*)$/i';

    public static function isSafe(mixed $url): bool
    {
        return is_string($url) && preg_match(self::PATTERN, trim($url)) === 1;
    }

    /**
     * The URL when it's safe, else null
     */
    public static function safe(mixed $url): ?string
    {
        return static::isSafe($url) ? trim($url) : null;
    }

    /**
     * Whether the link leaves the site, for rel="noopener"
     */
    public static function isExternal(string $url): bool
    {
        return (bool) preg_match('/^https?:\/\//i', $url);
    }
}
