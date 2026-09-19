<?php
/**
 * @var Collection<int, PublishedState> $allPublishedStates
 * @var DungeonRoute                    $dungeonroute
 */

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Collection;

$publishStates          = $allPublishedStates->pluck('name');
/** @var User|null $user */
$user                   = Auth::user();
$publishStatesAvailable = PublishedState::getAvailablePublishedStates($dungeonroute, $user);
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/publish', 'options' => [
    'publishSelector' => '#map_route_publish',
    'publishStatesAvailable' => $publishStatesAvailable,
]])

@include('common.forms.publishedstate', [
    'id' => 'map_route_publish',
    'name' => 'map_route_publish',
    'publishedStates' => $publishStates->all(),
    'availablePublishedStates' => $publishStatesAvailable->all(),
    'selected' => $dungeonroute->publishedstate->name,
    'subtexts' => $publishStates->mapWithKeys(static fn(string $publishState): array => [
        $publishState => __(sprintf('js.publish_state_subtext_%s', $publishState)),
    ])->all(),
])
