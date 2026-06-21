<?php
/**
 * @var Collection<int, PublishedState> $allPublishedStates
 * @var DungeonRoute               $dungeonroute
 */

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use Illuminate\Support\Collection;

$publishStates          = $allPublishedStates->pluck('name');
/** @var \App\Models\User|null $user */
$user                   = Auth::user();
$publishStatesAvailable = PublishedState::getAvailablePublishedStates($dungeonroute, $user);
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/publish', 'options' => [
    'publishSelector' => '#map_route_publish',
    'publishStates' => $publishStates,
    'publishStatesAvailable' => $publishStatesAvailable,
    'publishStateSelected' => $dungeonroute->publishedstate->name,
]])

{{ html()->select('map_route_publish', [], 1)->id('map_route_publish')->class('form-control selectpicker')->attribute('size', count($publishStates)) }}
