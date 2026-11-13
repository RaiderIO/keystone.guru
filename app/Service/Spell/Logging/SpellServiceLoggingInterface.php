<?php

namespace App\Service\Spell\Logging;

use App\Logging\Attributes\LogEvent;
use App\Logging\EventStructuredLoggingInterface;
use Monolog\Level;

/**
 * @method void importFromCsvUnableToParseFile()
 * @method void importFromCsvSpellIdEmpty()
 * @method void importFromCsvSpellAlreadySet(int $spellId)
 * @method void importFromCsvInsertNewSpell(int $spellId)
 * @method void importFromCsvInsertResult(int $updated, int $inserted)
 * @method void getCategoryNameFromClassNameUnableToFindCharacterClass(string $indexClassName)
 * @method void getCategoryNameFromClassNameUnableToFindCategory(string $categoryName)
 * @method void getCooldownGroupNameFromCooldownGroupUnableToFindCategory(string $cooldownGroup)
 */
#[LogEvent('importFromCsvUnableToParseFile', Level::Error)]
#[LogEvent('importFromCsvSpellIdEmpty', Level::Warning)]
#[LogEvent('importFromCsvSpellAlreadySet', Level::Warning)]
#[LogEvent('importFromCsvInsertNewSpell', Level::Debug)]
#[LogEvent('importFromCsvInsertResult', Level::Debug)]
#[LogEvent('getCategoryNameFromClassNameUnableToFindCharacterClass', Level::Error)]
#[LogEvent('getCategoryNameFromClassNameUnableToFindCategory', Level::Error)]
#[LogEvent('getCooldownGroupNameFromCooldownGroupUnableToFindCategory', Level::Error)]
interface SpellServiceLoggingInterface extends EventStructuredLoggingInterface
{
}
