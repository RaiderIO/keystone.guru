<?php

namespace App\Console\Commands\Localization\Larex\Traits;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait CorrectsLocalizationsHeader
{
    private const KSG_TO_CROWDIN_MAPPING = [
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

    public function convertLocalizationHeaderToKsgFormat(): bool
    {
        return $this->convertLocalizationHeader(array_flip(self::KSG_TO_CROWDIN_MAPPING));
    }

    public function convertLocalizationHeaderToCrowdinFormat(): bool
    {
        return $this->convertLocalizationHeader(self::KSG_TO_CROWDIN_MAPPING);
    }

    private function convertLocalizationHeader(array $mapping): bool
    {
        // Fix the localization.csv file
        $contents = file_get_contents(base_path('lang/localization.csv'));
        if ($contents === false) {
            $this->error('Failed to read lang/localization.csv');

            return 0;
        }

        // Find the end of the first line
        $headerEndPos = strpos($contents, "\n");
        if ($headerEndPos === false) {
            $this->error('Invalid localization.csv format (no newline found)');

            return 0;
        }

        // Extract and rewrite the header
        $header      = substr($contents, 0, $headerEndPos);
        $headerParts = explode(',', $header);

        foreach ($headerParts as &$headerPart) {
            foreach ($mapping as $old => $new) {
                // Transform old into new if necessary
                if ($headerPart === $old) {
                    $headerPart = $new;
                    break;
                }
            }
        }
        $header = implode(',', $headerParts);

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
