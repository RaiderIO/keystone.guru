<?php

namespace App\Console\Commands\Localization\Traits;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait ExportsNpcNames
{
    public function exportNpcNames(string $locale, array $data): bool
    {
        $exportToString = var_export($data, true);
        $exportToString = preg_replace_callback(
            '/\b(\d+)\s*=>/',
            function ($matches) {
                return "'" . $matches[1] . "' =>";
            },
            $exportToString
        );

        if (file_put_contents(
            lang_path(sprintf('%s/npcs.php', $locale)),
            '<?php ' . PHP_EOL . PHP_EOL . 'return ' . $exportToString . ';'
        )) {
            $this->info(sprintf('NPC names exported successfully to %s/npcs.php', $locale));

            return true;
        } else {
            $this->error(sprintf('Failed to write NPC names to %s/npcs.php', $locale));

            return false;
        }
    }
}
