<?php

namespace App\Console\Commands\Localization\Traits;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait ExportsTranslations
{
    public function exportTranslations(string $locale, string $fileName, array $data): bool
    {
        $exportToString = var_export($data, true);

        if (file_put_contents(
            lang_path(sprintf('%s/%s', $locale, $fileName)),
            '<?php ' . PHP_EOL . PHP_EOL . 'return ' . $exportToString . ';'
        )) {
            $this->info(sprintf('Translations exported successfully to %s/%s', $locale, $fileName));

            return true;
        } else {
            $this->error(sprintf('Failed to write translations to %s/%s', $locale, $fileName));

            return false;
        }
    }
}
