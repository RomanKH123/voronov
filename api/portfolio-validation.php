<?php
declare(strict_types=1);

function validPortfolioImage(string $value): bool
{
    if ($value === '') return true;
    if (preg_match('/[\x00-\x20\x7f\\\\<>"\']/', $value)) return false;
    // Root-relative images or explicit HTTP(S) URLs, never data:, javascript: or protocol-relative URLs.
    if (str_starts_with($value, '/') && !str_starts_with($value, '//')) return true;
    $parts = parse_url($value);
    return $parts !== false && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) &&
        !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) &&
        filter_var($value, FILTER_VALIDATE_URL) !== false;
}
