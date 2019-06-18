<?php


namespace App\Service\Season;

interface SeasonServiceInterface
{
    function getFirstSeason();

    function getSeasonAt($date);

    function getCurrentSeason();

    function getIterationsAt($date);

    function getAffixGroupIndexAt($date);

    function getDisplayedAffixGroups();
}