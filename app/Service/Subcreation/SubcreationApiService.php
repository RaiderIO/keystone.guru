<?php


namespace App\Service\Subcreation;

class SubcreationApiService implements SubcreationApiServiceInterface
{
    function getDungeonEaseTierListOverall(): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://subcreation.net/api/v0/dungeon_ease_tier_list_overall',
            CURLOPT_RETURNTRANSFER => 1
        ]);

        return (array)json_decode(curl_exec($ch), true);
    }
}