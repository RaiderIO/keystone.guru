<?php

namespace Database\Seeders;

use App\Models\Translation\Translation;
use Illuminate\Database\Seeder;
use Lang;

class TranslationsSeeder extends Seeder implements TableSeederInterface
{

    public function run(): void
    {
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
//                    $this->command->comment(sprintf('Skipping %s, no translations found', $fileName));
                    continue;
                }

                $translationAttributes = array_merge(
                    $translationAttributes,
                    $this->insertTranslationsRecursive($translations, $locale)
                );
            }
        }

//        $this->command->info(sprintf('Inserting %s translations', count($translationAttributes)));
        collect($translationAttributes)->chunk(1000)->each(function ($chunk) {

            Translation::from(DatabaseSeeder::getTempTableName(Translation::class))
                ->insert($chunk->toArray());
        });
    }

    private function insertTranslationsRecursive($translations, $locale, $prefix = ''): array
    {
        $result = [];
        foreach ($translations as $key => $translation) {
            if (is_array($translation)) {
                $result = array_merge(
                    $result,
                    $this->insertTranslationsRecursive($translation, $locale, $prefix . $key . '.')
                );
            } else {
                $result[] = [
                    'locale'      => $locale,
                    'key'         => $prefix . $key,
                    'translation' => $translation,
                ];
            }
        }

        return $result;
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
