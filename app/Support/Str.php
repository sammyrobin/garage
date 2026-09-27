<?php

declare(strict_types=1);

namespace Garage\Support;

/** String helpers that do not depend on mbstring/iconv (not guaranteed on shared hosting). */
final class Str
{
    private const ASCII = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ø' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'ñ' => 'n', 'Ñ' => 'n', 'ç' => 'c', 'Ç' => 'c', 'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe', '&' => ' and ', '+' => ' plus ',
    ];

    public static function slug(string $text, int $max = 150): string
    {
        $slug = strtolower(strtr($text, self::ASCII));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
        $slug = rtrim(substr($slug, 0, $max), '-');

        return $slug !== '' ? $slug : 'auto';
    }

    /** First $max characters (not bytes) of a UTF-8 string. */
    public static function cut(string $text, int $max): string
    {
        return preg_match('/^.{0,' . $max . '}/us', $text, $m) ? $m[0] : $text;
    }

    public static function length(string $text): int
    {
        return (int) preg_match_all('/./us', $text);
    }

    /** Trimmed, valid UTF-8, control characters removed (newlines kept only if $multiline). */
    public static function clean(mixed $value, bool $multiline = false): string
    {
        $text = is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
        if (!preg_match('//u', $text)) {
            $text = (string) preg_replace('/[\x80-\xFF]/', '', $text);
        }
        $pattern = $multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';

        return trim((string) preg_replace($pattern, $multiline ? '' : ' ', $text));
    }
}
