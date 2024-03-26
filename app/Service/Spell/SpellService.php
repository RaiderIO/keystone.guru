<?php

namespace App\Service\Spell;

use App\Models\CharacterClass;
use App\Models\Spell;
use App\Service\Spell\Logging\SpellServiceLoggingInterface;
use Illuminate\Support\Str;

class SpellService implements SpellServiceInterface
{
    public function __construct(private readonly SpellServiceLoggingInterface $log)
    {
    }

    public function importFromCsv(string $filePath): bool
    {
        $csvContents = file_get_contents($filePath);

        if ($csvContents === false) {
            $this->log->importFromCsvUnableToParseFile();

            return false;
        }

        $csv = str_getcsv_assoc($csvContents);

        $headers = array_shift($csv);

        $indexClassSpellId   = array_search('Class Spell ID', $headers);
        $indexCooldownGroup  = array_search('Cooldown Group', $headers);
        $indexClassIconName  = array_search('Class Icon Name', $headers);
        $indexClassSpellName = array_search('Class Spell', $headers);
        $indexClassName      = array_search('Class', $headers);
        $indexImagelink      = array_search('Imagelink', $headers);
        $indexActive         = array_search('Active', $headers);

        $spellAttributes = [];

        foreach ($csv as $index => $row) {

            $characterClassName = null;

            // General = null
            if ($row[$indexClassName] !== 'General') {
                $characterClass = $this->getClassFromRow($row[$indexClassName]);

                if ($characterClass === null) {
                    $this->log->importFromCsvUnableToFindCharacterClass($row[$indexClassName]);

                    break;
                }

                $characterClassName = __($characterClass->name, [], 'en');

                $categoryName = sprintf('spells.%s', Str::slug($characterClassName, '_'));

                if (!in_array($categoryName, Spell::ALL_CATEGORY)) {
                    $this->log->importFromCsvUnableToFindCategory($categoryName);

                    break;
                }
            }

            $spellAttributes[] = [
                'id'           => $row[$indexClassSpellId],
                'category'     => $characterClassName,
                'dispel_type'  => 'Magic',
                'icon_name'    => $row[$indexClassIconName],
                'name'         => $row[$indexClassSpellName],
                'schools_mask' => 0,
                'aura'         => 0,
                'selectable'   => $row[$indexActive],
            ];

            break;
        }

        dd($spellAttributes);
    }

    public function getClassFromRow(string $csvClass): ?CharacterClass
    {
        $result           = null;
        $characterClasses = CharacterClass::all();

        foreach ($characterClasses as $characterClass) {
            $name = __($characterClass->name, [], 'en');

            if (str_starts_with($csvClass, $name)) {
                $result = $characterClass;
                break;
            }
        }

        return $result;
    }
}
