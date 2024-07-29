<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Spell;
use App\Service\Traits\Curl;
use App\Service\Wowhead\Dtos\SpellDataResult;
use App\Service\Wowhead\Logging\WowheadServiceLoggingInterface;
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


    private const IDENTIFYING_TOKEN_SPELL_NAME        = '<meta property="og:title" content=';
    private const IDENTIFYING_TOKEN_SPELL_ICON_NAME   = 'WH.Gatherer.addData(29,';
    private const IDENTIFYING_TOKEN_SPELL_SCHOOL      = '<th>School</th>';
    private const IDENTIFYING_TOKEN_SPELL_DISPEL_TYPE = '<th>Dispel type</th>';

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
                    $this->log->downloadMissingSpellIconsFileExists($targetFile);

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

    public function getSpellData(int $spellId): ?SpellDataResult
    {
        $response = $this->getSpellPageHtml($spellId);

        // More hacky shit to scrape data we need
        $spellId       = 0;
        $cooldownGroup = Spell::COOLDOWN_GROUP_UNKNOWN; // I can't find info on this on Wowhead?
        $dispelType    = '';
        $iconName      = '';
        $name          = '';
        $schoolsMask   = 0;
        $aura          = false;

        // When set to true, the next line will contain the school.
        $schoolFound = $dispelTypeFound = false;

        $lines = explode(PHP_EOL, $response);
        foreach ($lines as $line) {
            $line = trim($line);

            // Spell icon name
            if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_ICON_NAME)) {
                // WH.Gatherer.addData(29, 3, {"135988":{"name":"spell_ice_lament","icon":"spell_ice_lament"}});
                if (preg_match('/{.*}/', $line, $matches)) {
                    $jsonString = $matches[0];
                    $json       = json_decode($jsonString, true);

                    if (!is_array($json)) {
                        $this->log->getSpellDataIconNameNotFound($line, $json);
                        continue;
                    }

                    // I don't know the number of the first array key - convert it to 0 always
                    $json     = array_values($json);
                    $iconName = $json[0]['icon'];
                }
            } // Spell name
            else if (str_contains($line, self::IDENTIFYING_TOKEN_SPELL_NAME)) {
                $name = str_replace([self::IDENTIFYING_TOKEN_SPELL_NAME, '"', '>'], '', $line);
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
            }

        }

        return new SpellDataResult(
            $spellId, $cooldownGroup, $dispelType, $iconName, $name, $schoolsMask, $aura
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

    public function getSpellPageHtml(int $spellId): string
    {
        return $this->curlGet(
            sprintf('https://wowhead.com/spell=%s',
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
