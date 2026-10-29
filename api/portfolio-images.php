<?php
declare(strict_types=1);

// Prefer an existing local WebP without changing saved portfolio records or external URLs.
function portfolioWebpRecord(array $work): array
{
    $image = $work['image'] ?? '';
    if (!is_string($image) || !str_starts_with($image, '/img/') ||
        str_contains($image, '..') || str_contains($image, '\\') || str_contains($image, "\0") ||
        !preg_match('/\.(?:png|jpe?g|gif)$/i', $image)) {
        return $work;
    }
    $webp = preg_replace('/\.(?:png|jpe?g|gif)$/i', '.webp', $image);
    $imageDirectory = realpath(dirname(__DIR__) . '/img');
    $file = realpath(dirname(__DIR__) . $webp);
    if ($imageDirectory !== false && $file !== false && is_file($file) &&
        str_starts_with($file, $imageDirectory . DIRECTORY_SEPARATOR)) {
        $work['image'] = $webp;
    }
    return $work;
}
