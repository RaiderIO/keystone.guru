<?php

namespace App\Console\Commands\Localization\Datatables;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use Exception;
use Illuminate\Console\Command;

class ConvertLocalizations extends Command
{
    use ExportsTranslations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:dt-convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts all fetched Datatables localizations to translations Laravel understands.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): void
    {
        foreach (glob(
            base_path('app/Console/Commands/Localization/Datatables/Lang/*.json'),
        ) as $localePath) {
            $translations = json_decode(file_get_contents($localePath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error(sprintf('Invalid JSON in %s: %s', $localePath, json_last_error_msg()));
                continue;
            }

            $locale   = basename($localePath, '.json');
            $fileName = sprintf('%s.json', $locale);
            if ($this->exportTranslations($locale, 'datatables.php', $translations)) {
                $this->info(sprintf('Successfully converted %s', $fileName));
            } else {
                $this->error(sprintf('Failed to convert %s', $fileName));
            }
        }
    }
}
