<?php
require_once __DIR__ . '/bootstrap.php';
// Disabled: silently persisting contact data during a cookie flow is not a valid consent mechanism.
jsonResponse(['success' => false, 'message' => 'Endpoint disabled'], 410);
