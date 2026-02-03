<?php

namespace App\Console\Commands\Localization\Datatables;

use App\Service\Traits\Curl;
use Exception;
use Illuminate\Console\Command;

class DownloadLocalizations extends Command
{
    use Curl;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:dt-download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches all localizations from the datatables website.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $localeMapping = [
            'en_US' => 'en-GB',
            'ko_KR' => 'ko',
            'ru_RU' => 'ru',
            'uk_UA' => 'uk',
            'zh_CN' => 'zh',
            'zh_TW' => 'zh',
        ];

        foreach (config('language.all') as $locale) {
            if (in_array($locale['long'], ['ho_HO']) || ($locale['ai'] ?? false)) {
                $this->info(sprintf('Skipping %s', $locale['long']));
                continue;
            }

            $localeDt = str_replace('_', '-', $locale['long']);
            $this->info(sprintf('Downloading %s', $locale['long']));

            $url = sprintf(
                'https://cdn.datatables.net/plug-ins/2.3.5/i18n/%s.json',
                $localeMapping[$locale['long']] ?? $localeDt,
            );

            $response = $this->curlGet($url);

            if (empty($response)) {
                $this->error(sprintf('Failed to download localizations for %s', $locale['long']));
                continue;
            }

            // Save the response to a file or process it as needed
            if (file_put_contents(
                base_path(
                    sprintf('app/Console/Commands/Localization/Datatables/Lang/%s.json', $locale['long']),
                ),
                $response,
            )) {
                $this->info(sprintf('- Successfully saved localizations for %s', $locale['long']));
            } else {
                $this->error(sprintf('- Failed to save localizations for %s', $locale['long']));
            }
        }
    }
}
