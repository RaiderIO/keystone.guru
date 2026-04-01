<?php

namespace App\Console\Commands\Localization;

use Illuminate\Console\Command;

abstract class BaseSyncCommand extends Command {

    protected function hasAILanguage(string $locale): bool
    {
        foreach (config('language.all') as $language) {
            if ($language['long'] === sprintf('%s_ai', $locale)) {
                return $language['ai'] ?? false;
            }
        }

        return false;
    }
}
