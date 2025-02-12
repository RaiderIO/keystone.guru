<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Service\Traits\Curl;
use App\Service\Wowhead\Dtos\SpellDataResult;
use App\Service\Wowhead\Logging\WowheadServiceLoggingInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Str;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class WowheadService implements WowheadServiceInterface
{
    use Curl;

    private const IDENTIFYING_TOKEN_HEALTH     = '$(document).ready(function(){$(".infobox li").last().after("<li><div><span class=\"tip\" onmouseover=\"WH.Tooltip.showAtCursor(event, ';
    private const IDENTIFYING_TOKEN_DISPLAY_ID = 'linksButton.dataset.displayId =';


    private const IDENTIFYING_TOKEN_SPELL_DOES_NOT_EXIST    = 'Spell #%d doesn\'t exist. It may have been removed from the game.';
    private const IDENTIFYING_TOKEN_SPELL_NAME              = '<meta property="og:title" content=';
    private const IDENTIFYING_TOKEN_SPELL_ICON_NAME         = 'WeakAuraExport.setOptions(';
    private const IDENTIFYING_REGEX_SPELL_ICON_NAME_CLASSIC = '/Icon\.create\("([^"]+)"/';
    private const IDENTIFYING_REGEX_SPELL_CATEGORY          = '/WH\.Gatherer\.addData\(13,\s*1,\s*\{[^}]*"name_enus":"([^"]+)"}/';
    private const IDENTIFYING_TOKEN_SPELL_MECHANIC          = '<th>Mechanic</th>';
    private const IDENTIFYING_TOKEN_SPELL_SCHOOL            = '<th>School</th>';
    private const IDENTIFYING_TOKEN_SPELL_DISPEL_TYPE       = '<th>Dispel type</th>';
    private const IDENTIFYING_TOKEN_SPELL_CAST_TIME         = '<th>Cast time</th>';
    private const IDENTIFYING_TOKEN_SPELL_DURATION          = '<th>Duration</th>';

    public function __construct(
        private readonly WowheadServiceLoggingInterface $log
    ) {
    }

    public function getNpcHealth(GameVersion $gameVersion, Npc $npc): ?int
    {
        $response = $this->getNpcPageHtml($gameVersion, $npc);

        // Hacky shit to scrape it
        $health = null;
        $lines  = explode(PHP_EOL, $response);
        foreach ($lines as $line) {
            $line = trim($line);

            if (!str_contains($line, self::IDENTIFYING_TOKEN_HEALTH)) {
                continue;
            }

            // Extract the html we want to parse
            /** @noinspection HtmlUnknownAttribute */
            $html = sprintf('<table %s</table>', $this->getStringBetween($line, '<table', '</table>'));

            // Find the health value from this little html

            $dom = new Dom();
            try {
                $dom->loadStr($html);
                $tds = $dom->getElementsbyTag('td');

                $grabNext = false;

                foreach ($tds as $td) {
                    if ($td->innerHtml === 'Normal&nbsp;&nbsp;') {
                        $grabNext = true;
                    } else if ($grabNext) {
                        $possibleHealth = (int)str_replace(',', '', (string)$td->innerHtml);
                        if ($possibleHealth > 0) {
                            $health = $possibleHealth;
                            break;
                        }
                    }
                }
            } catch (ChildNotFoundException|StrictException|LogicalException|ContentLengthException|CircularException|NotLoadedException $ex) {
                $this->log->getNpcHealthHtmlParsingException($ex);
            }
        }

        return $health;
    }

    public function downloadMissingSpellIcons(): bool
    {
        $result = true;

        $this->log->downloadMissingSpellIconsStart();
        try {
            $targetFolder = resource_path('assets/images/spells');
            Spell::whereNot('icon_name', '')->each(function (Spell $spell) use (&$result, $targetFolder) {
                $targetFile = sprintf('%s/%s.jpg', $targetFolder, $spell->icon_name);

                // Not missing = we continue
                if (file_exists($targetFile)) {

                    return true;
                }

                $result = $result && $this->downloadSpellIcon($spell, $targetFolder);

                // Don't DDOS
                $this->sleep();

                return true;
            }, 1000);
        } finally {
            $this->log->downloadMissingSpellIconsEnd();
        }

        return $result;
    }

    public function downloadSpellIcon(Spell $spell, string $targetFolder): bool
    {
        $fileName       = sprintf('%s.jpg', $spell->icon_name);
        $targetFilePath = sprintf('%s/%s', $targetFolder, $fileName);

        $result = $this->curlSaveToFile(
            sprintf('https://wow.zamimg.com/images/wow/icons/large/%s', $fileName),
            $targetFilePath
        );

        $this->log->downloadSpellIconDownloadResult($targetFilePath, $result);

        return $result;
    }

    public function getNpcDisplayId(GameVersion $gameVersion, Npc $npc): ?int
    {
        $response = $this->getNpcPageHtml($gameVersion, $npc);

        // Hacky shit to scrape it
        $displayId = null;
        $lines     = explode(PHP_EOL, $response);
        foreach ($lines as $line) {
            $line = trim($line);

            if (!str_contains($line, self::IDENTIFYING_TOKEN_DISPLAY_ID)) {
                continue;
            }

            $displayId = (int)str_replace([self::IDENTIFYING_TOKEN_DISPLAY_ID], '', $line);
            break;
        }

        return $displayId;
    }

    public function getSpellData(GameVersion $gameVersion, int $spellId): ?SpellDataResult
    {
        $response = $this->getSpellPageHtml($gameVersion, $spellId);

        // More hacky shit to scrape data we need
        $mechanic      = null;
        $category      = Spell::CATEGORY_UNKNOWN;
        $cooldownGroup = sprintf('spells.cooldown_group.%s', Spell::COOLDOWN_GROUP_UNKNOWN); // I can't find info on this on Wowhead?
        $dispelType    = '';
        $iconName      = '';
        $name          = '';
        $schoolsMask   = 0;
        $castTime      = null;
        $duration      = null;

        // When set to true, the next line will contain the school.
        $mechanicFound = $schoolFound = $dispelTypeFound = $castTimeFound = $durationFound = false;
        $mechanicSet   = $schoolSet = $dispelTypeSet = $castTimeSet = $durationSet = false;

        $lines = explode(PHP_EOL, $response);
        foreach ($lines as $line) {
            $line = trim($line);

            // Check if the spell was removed
            if (str_contains($line, sprintf(self::IDENTIFYING_TOKEN_SPELL_DOES_NOT_EXIST, $spellId))) {
                $this->log->getSpellDataSpellDoesNotExist($gameVersion->key, $spellId);

                // Like, we're done, don't return anything
                return null;
            } // Spell icon name
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_ICON_NAME)) {
                // WeakAuraExport.setOptions({"id":322486,"name":"Overgrowth","iconFilename":"inv_misc_herb_nightmarevine_stem","appliesABuff":true,"display":"progress-bar-medium","trigger":"player-has-debuff"});
                if (preg_match('/{.*}/', $line, $matches)) {
                    $jsonString = $matches[0];
                    $json       = json_decode($jsonString, true);

                    if (!is_array($json)) {
                        $this->log->getSpellDataIconNameNotFound($line, $json);
                        continue;
                    }

                    if ((int)$json['id'] !== $spellId) {
                        $this->log->getSpellDataIconNameSpellIdDoesNotMatch($line, $json, $spellId);
                        continue;
                    }

                    // I don't know the number of the first array key - convert it to 0 always
                    $iconName = $json['iconFilename'];
                }
            } else if ($gameVersion->key === GameVersion::GAME_VERSION_CLASSIC_ERA &&
                preg_match(self::IDENTIFYING_REGEX_SPELL_ICON_NAME_CLASSIC, $line, $matches)) {
                $iconName = $matches[1];
            } else if (preg_match(self::IDENTIFYING_REGEX_SPELL_CATEGORY, $line, $matches)) {
                $category = Str::slug($matches[1], '_');
            }
            // Mechanic
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_MECHANIC)) {
                $mechanicFound = true;
            } // Triggered on the next line
            else if ($mechanicFound) {
                $mechanic = str_replace(['<td>', '</td>'], '', $line);
                if (str_contains($mechanic, 'n/a')) {
                    $mechanic = null;
                } else {
                    $mechanic = sprintf('spells.mechanic.%s', Str::slug($mechanic));
                }
                $mechanicFound = false;
                $mechanicSet   = true;
            } // Spell name
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_NAME)) {
                $name = html_entity_decode(
                    str_replace([self::IDENTIFYING_TOKEN_SPELL_NAME, '"', '>'], '', $line),
                    ENT_QUOTES | ENT_XML1
                );
            } // Spell school
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_SCHOOL)) {
                $schoolFound = true;
            } // Triggered on the next line
            else if ($schoolFound) {
                $schoolsStr = str_replace(['<td>', '</td>'], '', $line);
                $schools    = explode(', ', $schoolsStr);

                foreach ($schools as $school) {
                    if (isset(Spell::ALL_SCHOOLS[$school])) {
                        $schoolsMask |= Spell::ALL_SCHOOLS[$school];
                    } else {
                        $this->log->getSpellDataSpellSchoolNotFound($schoolsStr, $school);
                    }
                }
                $schoolFound = false;
                $schoolSet   = true;
            } // Spell dispel type
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_DISPEL_TYPE)) {
                $dispelTypeFound = true;
            } // Triggered on the next line
            else if ($dispelTypeFound) {
                $dispelType = str_replace(['<td>', '</td>'], '', $line);
                if (str_contains($dispelType, 'n/a')) {
                    $dispelType = Spell::DISPEL_TYPE_NOT_AVAILABLE;
                } else if (!in_array($dispelType, Spell::ALL_DISPEL_TYPES)) {
                    $this->log->getSpellDataSpellDispelTypeNotFound($dispelType);

                    $dispelType = Spell::DISPEL_TYPE_UNKNOWN;
                }
                $dispelTypeFound = false;
                $dispelTypeSet   = true;
            } // Cast time
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_CAST_TIME)) {
                $castTimeFound = true;
            } // Triggered on the next line
            else if ($castTimeFound) {
                $castTime = str_replace(['<td>', '</td>'], '', $line);
                if (str_contains($castTime, 'n/a')) {
                    $castTime = null;
                } else if (str_contains($castTime, 'Instant')) {
                    $castTime = 0;
                } else {
                    $castTime = CarbonInterval::fromString($castTime)->totalMilliseconds;
                }
                $castTimeFound = false;
                $castTimeSet   = true;
            } // Duration
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_DURATION)) {
                $durationFound = true;
            } // Triggered on the next line
            else if ($durationFound) {
                /** @noinspection HtmlDeprecatedAttribute */
                $duration = str_replace(['<td width="100%">', '</td>'], '', $line);
                if (str_contains($duration, 'n/a')) {
                    $duration = null;
                } else {
                    $duration = CarbonInterval::fromString($duration)->totalMilliseconds;
                }
                $durationFound = false;
                $durationSet   = true;
            }
        }

        if (!$mechanicSet || !$schoolSet || !$dispelTypeSet || !$castTimeSet || !$durationSet) {
            $this->log->getSpellDataDataNotSet($mechanicSet, $schoolSet, $dispelTypeSet, $castTimeSet, $durationSet);
        }

        return new SpellDataResult(
            $spellId,
            $mechanic,
            sprintf('spells.category.%s', $category),
            $cooldownGroup,
            $dispelType,
            $iconName,
            $name,
            $schoolsMask,
            $castTime,
            $duration
        );
    }


    public function sleep(int $seconds = 1): void
    {
        sleep($seconds);
    }

    public function getNpcPageHtml(GameVersion $gameVersion, Npc $npc): string
    {
        return $this->curlGet(
            sprintf('https://wowhead.com/%snpc=%s/%s',
                $gameVersion->key === GameVersion::GAME_VERSION_RETAIL ? '' : $gameVersion->key . '/',
                $npc->id,
                Str::slug($npc->name)
            )
        );
    }

    public function getSpellPageHtml(GameVersion $gameVersion, int $spellId): string
    {
        return $this->curlGet(
            sprintf('https://wowhead.com/%sspell=%s',
                $gameVersion->key === GameVersion::GAME_VERSION_RETAIL ? '' : $gameVersion->key . '/',
                $spellId
            )
        );
    }

    private function getStringBetween(string $string, string $start, string $end): string
    {
        $string = ' ' . $string;
        $ini    = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }
}
