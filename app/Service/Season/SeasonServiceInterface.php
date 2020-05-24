<?php


namespace App\Service\Season;

interface SeasonServiceInterface
{
    function getSeasons();

    function getFirstSeason();

    function getSeasonAt($date);

    function getCurrentSeason();

    function getIterationsAt($date);

    function getAffixGroupIndexAt($date);

    function getDisplayedAffixGroups($iterationOffset);
}