<?php
declare(strict_types=1);

// Старый генератор городских страниц выведен из использования.
// Известные адреса перенаправляются правилами в .htaccess.
http_response_code(410);
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex');
readfile(__DIR__ . '/404.html');
