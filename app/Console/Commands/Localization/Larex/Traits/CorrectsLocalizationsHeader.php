<?php

namespace App\Console\Commands\Localization\Larex\Traits;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait CorrectsLocalizationsHeader
{
    public function correctLocalizationHeader(): bool
    {
        // Fix the localization.csv file
        $contents = file_get_contents(base_path('lang/localization.csv'));
        if ($contents === false) {
            $this->error('Failed to read lang/localization.csv');

            return 0;
        }

        $localeMapping = [
            'de-DE' => 'de',
            'en-US' => 'en',
            'es-ES' => 'es-ES',
            'es-MX' => 'es-MX',
            'fr-FR' => 'fr',
            'ho-HO' => 'ho-HO',
            'it-IT' => 'it',
            'ko-KR' => 'ko',
            'pt-BR' => 'pt-BR',
            'ru-RU' => 'ru',
            'uk-UA' => 'uk',
            'zh-CN' => 'zh-CN',
            'zh-TW' => 'zh-TW',
        ];

        // Find the end of the first line
        $headerEndPos = strpos($contents, "\n");
        if ($headerEndPos === false) {
            $this->error('Invalid localization.csv format (no newline found)');

            return 0;
        }

        // Extract and rewrite the header
        $header = substr($contents, 0, $headerEndPos);
        foreach ($localeMapping as $old => $new) {
            $header = str_replace($old, $new, $header);
        }

        // Combine modified header and original body
        $contents = $header . substr($contents, $headerEndPos);

        // Overwrite file or continue with $contents
        $writeResult = file_put_contents(base_path('lang/localization.csv'), $contents);
        if ($writeResult === false) {
            $this->error('Failed to write lang/localization.csv');

            return 0;
        }

        return $writeResult;
    }
}
