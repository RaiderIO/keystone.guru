<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc;
use App\Service\Traits\Curl;
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

    /**
     * @param GameVersion $gameVersion
     * @param Npc         $npc
     * @return int|null
     */
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

            if (strpos($line, self::HEALTH_IDENTIFYING_TOKEN) === false) {
                continue;
            }

            // Extract the html we want to parse
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
                        $possibleHealth = (int)str_replace(',', '', $td->innerHtml);
                        if ($possibleHealth > 0) {
                            $health = $possibleHealth;
                            break;
                        }
                    }
                }
            } catch (ChildNotFoundException|StrictException|LogicalException|ContentLengthException|CircularException|NotLoadedException $e) {
            }
        }

        return $health;
    }

    /**
     * @param string $string
     * @param string $start
     * @param string $end
     * @return false|string
     */
    private function getStringBetween(string $string, string $start, string $end)
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
