<?php

namespace Database\Seeders;

use App\Models\Translation\Translation;
use Illuminate\Database\Seeder;
use Lang;

class TranslationsSeeder extends Seeder implements TableSeederInterface
{
    /** Kept comfortably below MySQL's 65535 bound-parameter limit (3 columns per row). */
    private const INSERT_CHUNK_SIZE = 10000;

    public function run(): void
    {
        /** @var array<int, array<string, string>> $translationAttributes */
        $translationAttributes = [];

        // Loop over all locales
        foreach (glob(lang_path('/*'), GLOB_ONLYDIR) as $localePath) {
            $locale = basename($localePath);
            // Loop over all files in the locale
            foreach (glob(sprintf('%s/*.php', $localePath)) as $file) {
                $fileName = basename($file, '.php');

                // Grab all the translations from the file
                $translations = Lang::get($fileName, [], $locale);

                if (!is_array($translations)) {
                    continue;
                }

                $this->insertTranslationsRecursive($translations, $locale, $fileName . '.', $translationAttributes);
            }
        }

        foreach (array_chunk($translationAttributes, self::INSERT_CHUNK_SIZE) as $chunk) {
            Translation::from(DatabaseSeeder::getTempTableName(Translation::class))
                ->insert($chunk);
        }
    }

    /**
     * @param array<string, mixed>              $translations
     * @param string                            $locale
     * @param string                            $prefix
     * @param array<int, array<string, string>> $result       Accumulator, appended to by reference to avoid
     *                                                        the quadratic cost of repeatedly array_merge-ing
     *                                                        a growing result set.
     */
    private function insertTranslationsRecursive(array $translations, string $locale, string $prefix, array &$result): void
    {
        foreach ($translations as $key => $translation) {
            if (is_array($translation)) {
                $this->insertTranslationsRecursive($translation, $locale, $prefix . $key . '.', $result);
            } else {
                $result[] = [
                    'locale'      => $locale,
                    'key'         => $prefix . $key,
                    'translation' => (string)$translation,
                ];
            }
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [Translation::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
