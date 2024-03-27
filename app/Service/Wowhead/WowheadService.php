<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc;
use App\Models\Spell;
use App\Service\Traits\Curl;
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

    private const HEALTH_IDENTIFYING_TOKEN = '$(document).ready(function(){$(".infobox li").last().after("<li><div><span class=\"tip\" onmouseover=\"WH.Tooltip.showAtCursor(event, ';

    public function __construct(
        private readonly WowheadServiceLoggingInterface $log
    ) {
    }

    public function getNpcHealth(GameVersion $gameVersion, Npc $npc): ?int
    {
        $response = $this->curlGet(
            sprintf('https://wowhead.com/%snpc=%s/%s',
                $gameVersion->key === GameVersion::GAME_VERSION_RETAIL ? '' : $gameVersion->key . '/',
                $npc->id,
                Str::slug($npc->name)
            )
        );

        // Hacky shit to scrape it
        $health = 0;
        $lines  = explode(PHP_EOL, $response);
        foreach ($lines as $line) {
            $line = trim($line);

            if (!str_contains($line, self::HEALTH_IDENTIFYING_TOKEN)) {
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
            } catch (ChildNotFoundException|StrictException|LogicalException|ContentLengthException|CircularException|NotLoadedException) {
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
            Spell::each(function (Spell $spell) use (&$result, $targetFolder) {
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

    public function sleep(int $seconds = 1): void
    {
        sleep($seconds);
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
