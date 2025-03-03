<?php

namespace App\Service\Spell;

use App\Models\CharacterClass;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\Spell\Logging\SpellServiceLoggingInterface;
use Illuminate\Support\Str;

class SpellService implements SpellServiceInterface
{
    public function __construct(
        private readonly SpellRepositoryInterface $spellRepository,
        private readonly SpellServiceLoggingInterface $log
    )
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
        $indexActive         = array_search('Active (True/False)', $headers);

        $spellsAttributes = [];

        foreach ($csv as $index => $row) {

            $spellId = $row[$indexClassSpellId];

            if (empty($spellId)) {
                $this->log->importFromCsvSpellIdEmpty();

                continue;
            }

            if (isset($spellsAttributes[$spellId])) {
                $this->log->importFromCsvSpellAlreadySet($spellId);

                continue;
            }

            $categoryName = $this->getCategoryNameFromRowClassName($row[$indexClassName]);

            $cooldownGroupName = $this->getCooldownGroupNameFromRowCooldownGroup($row[$indexCooldownGroup]);

            $spellsAttributes[$spellId] = [
                'id'             => $spellId,
                'category'       => $categoryName,
                'cooldown_group' => $cooldownGroupName,
                'dispel_type'    => 'Magic',
                'icon_name'      => $row[$indexClassIconName],
                'name'           => $row[$indexClassSpellName],
                'schools_mask'   => 0,
                'aura'           => 0,
                'selectable'     => $row[$indexActive] === 'TRUE',
            ];
        }

        // Update all found spells or insert them if they didn't exist
        $spellsById = Spell::all()->keyBy('id');

        $updated = $inserted = 0;
        foreach ($spellsAttributes as $spellId => $spellAttributes) {
            if ($spellsById->has($spellId)) {
                $spell = $spellsById->get($spellId);

                $spell->update($spellAttributes);
                $updated++;
            } else {
                if (Spell::create($spellAttributes)) {
                    $this->log->importFromCsvInsertNewSpell($spellId);
                    $inserted++;
                }
            }
        }

        $this->log->importFromCsvInsertResult($updated, $inserted);

        return true;
    }

    public function getCategoryNameFromRowClassName(string $rowClassName): ?string
    {
        // Try to match the category directly first
        $categorySlug = Str::slug($rowClassName, '_');

        if (!in_array($categorySlug, Spell::ALL_CATEGORIES)) {
            // Try to find the associated class first, then use that class to identify the category
            $characterClass = $this->getCharacterClassFromClassName($rowClassName);

            if ($characterClass === null) {
                $this->log->getCategoryNameFromClassNameUnableToFindCharacterClass($rowClassName);

                return null;
            }

            // Based on the character class name translation, find the category
            $characterClassName = __($characterClass->name, [], 'en_US');

            $categorySlug = Str::slug($characterClassName, '_');

            if (!in_array($categorySlug, Spell::ALL_CATEGORIES)) {
                $this->log->getCategoryNameFromClassNameUnableToFindCategory($categorySlug);

                return null;
            }
        }

        return sprintf('spells.category.%s', $categorySlug);
    }

    public function getCharacterClassFromClassName(string $csvClass): ?CharacterClass
    {
        $result           = null;
        $characterClasses = CharacterClass::all();

        foreach ($characterClasses as $characterClass) {
            $name = __($characterClass->name, [], 'en_US');

            if (str_starts_with($csvClass, $name)) {
                $result = $characterClass;
                break;
            }
        }

        return $result;
    }

    public function getCooldownGroupNameFromRowCooldownGroup(string $cooldownGroup): ?string
    {
        $cooldownGroupSlug = Str::slug($cooldownGroup, '_');

        if (!in_array($cooldownGroupSlug, Spell::ALL_COOLDOWN_GROUPS)) {
            $this->log->getCooldownGroupNameFromCooldownGroupUnableToFindCategory($cooldownGroupSlug);

            return null;
        }

        return sprintf('spells.cooldown_group.%s', $cooldownGroupSlug);
    }

    /**
     * @return int[]
     */
    public function getMissingSpellIds(): array
    {
        return $this->spellRepository->getMissingSpellIds();
    }
}
