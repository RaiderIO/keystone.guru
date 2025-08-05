<?php

namespace App\Larex;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Lukasss93\Larex\Console\LarexImportCommand;
use Lukasss93\Larex\Contracts\Importer;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class GroupFilteringLaravelImporter implements Importer
{
    public static function description(): string
    {
        return 'Import data from Laravel localization files to CSV (with group include/exclude)';
    }

    public function handle(LarexImportCommand $command): Collection
    {
        $includeGroups = Str::of($command->option('include'))->explode(',')->reject(fn($i) => empty($i));
        $excludeGroups = Str::of($command->option('exclude'))->explode(',')->reject(fn($i) => empty($i));

        $languages = collect([]);
        $rawValues = collect([]);

        $files = File::glob(lang_path('**/*.php'));

        foreach ($files as $file) {
            $items = include $file;
            $group = pathinfo($file, PATHINFO_FILENAME);
            $lang  = str_replace('_', '-', basename(dirname($file)));

            // group filtering logic
            if ($includeGroups->isNotEmpty() && !$includeGroups->contains($group)) {
                continue;
            }

            if ($excludeGroups->isNotEmpty() && $excludeGroups->contains($group)) {
                continue;
            }

            if (!$languages->contains($lang)) {
                $languages->push($lang);
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator($items),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $path = [];
            foreach ($iterator as $key => $value) {
                $path[$iterator->getDepth()] = $key;
                if (!is_array($value)) {
                    $rawValues->push([
                        'group' => $group,
                        'key'   => implode('.', array_slice($path, 0, $iterator->getDepth() + 1)),
                        'lang'  => $lang,
                        'value' => $value,
                    ]);
                }
            }
        }

        $data = collect([]);

        foreach ($rawValues as $rawValue) {
            $index = $data->search(fn($item) => $item['group'] === $rawValue['group'] && $item['key'] === $rawValue['key']
            );

            if ($index === false) {
                $output = [
                    'group' => $rawValue['group'],
                    'key'   => $rawValue['key'],
                ];

                foreach ($languages as $lang) {
                    $output[$lang] = $rawValue['lang'] === $lang ? $rawValue['value'] : '';
                }

                $data->push($output);
            } else {
                foreach ($languages as $lang) {
                    if ($rawValue['lang'] === $lang) {
                        $new        = $data->get($index);
                        $new[$lang] = $rawValue['value'];
                        $data->put($index, $new);
                    }
                }
            }
        }

        return $data->values();
    }
}
