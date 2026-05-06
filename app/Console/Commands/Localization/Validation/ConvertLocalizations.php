<?php

namespace App\Console\Commands\Localization\Validation;

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
    protected $signature = 'localization:validation-convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts all Validation localizations to translations Laravel understands.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $localeMapping = [
            'de_DE' => 'de',
            'en_US' => 'en',
            'es_ES' => 'es',
            'es_MX' => 'es',
            'fr_FR' => 'fr',
            'it_IT' => 'it',
            'ko_KR' => 'ko',
            'pt_BR' => 'pt_BR',
            'ru_RU' => 'ru',
            'uk_UA' => 'uk',
            'zh_CN' => 'zh_CN',
            'zh_TW' => 'zh_TW',
        ];

        foreach ($localeMapping as $locale => $localePath) {
            $translations = json_decode(
                file_get_contents(
                    base_path(
                        sprintf('vendor/laravel-lang/lang/locales/%s/php.json', $localePath),
                    ),
                ),
                true,
            );

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error(sprintf('Invalid JSON in %s: %s', $localePath, json_last_error_msg()));
                continue;
            }

            // Recursively dot notations to keys
            $translations = $this->convertDotNotationToArray($translations);

            if ($this->exportTranslations($locale, 'validation.php', $translations)) {
                $this->info(sprintf('Successfully converted %s.json', $locale));
            } else {
                $this->error(sprintf('Failed to convert %s.json', $locale));
            }
        }
    }

    /**
     * Convert nested arrays to dot notation.
     *
     * @param  array  $array
     * @param  string $prefix
     * @return array
     */
    protected function convertDotNotationToArray(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            /**
             * "password": "The provided password is incorrect.",
             * "password.letters": "The :attribute field must contain at least one letter.",
             *
             * You cannot assign "password" to the root of the array, password will be an array, inside of that
             * will be keys. But this violates that rule, so we skip it.
             */
            if ($key === 'password' && is_string($value)) {
                continue;
            }

            if (is_array($value)) {
                // Recursively convert nested arrays
                $result = $this->convertDotNotationToArray($value);
            } elseif (str_contains((string)$key, '.')) {
                // Handle dot notation keys
                $keys = explode('.', (string)$key);
                $temp = &$result;

                // Build key hierarchy
                foreach ($keys as $subKey) {
                    if (!isset($temp[$subKey])) {
                        $temp[$subKey] = [];
                    }

                    $temp = &$temp[$subKey];
                }

                // Assign the value to the last key (by reference)
                $temp = $value;
            } else {
                // Directly assign non-array values
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
