<?php
declare(strict_types=1);
// Parse actual renderer output with an HTML parser, including hostile saved records.
$fixtures = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
foreach ($fixtures as $html) {
    $dom = new DOMDocument();
    @$dom->loadHTML('<!doctype html><html><body>' . $html . '</body></html>');
    foreach ($dom->getElementsByTagName('*') as $element) {
        if (in_array(strtolower($element->tagName), ['script', 'iframe', 'object', 'embed'], true)) {
            throw new RuntimeException('Executable element in renderer output');
        }
        foreach ($element->attributes as $attribute) {
            if (str_starts_with(strtolower($attribute->name), 'on')) {
                // Existing public image error handlers are fixed application code.
                if ($attribute->name !== 'onerror' || !preg_match("/^this\.parentElement\.classList\.add\('(work-card__image|project_preview)--broken'\);this\.remove\(\);$/", $attribute->value)) {
                    throw new RuntimeException('Unexpected event attribute: ' . $attribute->name);
                }
            }
            if (in_array(strtolower($attribute->name), ['autofocus', 'data-attack'], true)) {
                throw new RuntimeException('Hostile value escaped its attribute');
            }
        }
        if ($element->tagName === 'img' && !preg_match('~^https?://~i', $element->getAttribute('src'))) {
            throw new RuntimeException('Unsafe image protocol');
        }
    }
}
echo 'PASS: parsed ' . count($fixtures) . " hostile HTML fixtures without injected elements or handlers.\n";
