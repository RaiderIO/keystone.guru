<?php

/**
 * Flattens every lang file of a locale into a single dotted-key => value JSON map on stdout.
 *
 * Usage (from the repo root): docker compose exec -T app php .claude/skills/ai-locale-translation/scripts/dump_locale.php de_DE_ai
 */
$locale = $argv[1] ?? null;
if ($locale === null) {
    fwrite(STDERR, "usage: dump_locale.php <locale>\n");

    exit(1);
}

$flat = [];
foreach (glob(base_path_guess() . "/lang/$locale/*.php") as $file) {
    $contents = require $file;
    if (!is_array($contents)) {
        continue;
    }

    $walk = function (array $array, string $prefix) use (&$walk, &$flat): void {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $walk($value, $prefix . $key . '.');
            } else {
                $flat[$prefix . $key] = $value;
            }
        }
    };

    $walk($contents, basename($file, '.php') . '.');
}

echo json_encode($flat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function base_path_guess(): string
{
    return dirname(__DIR__, 4);
}
