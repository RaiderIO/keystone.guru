<?php
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * This is only visible for mobile users: a segmented control of every game version, on top of the dungeon
 * sheet - or in the navbar menu on pages that have no dungeon sheet.
 *
 * @var Collection<int, GameVersion> $allGameVersions
 * @var GameVersion                  $currentUserGameVersion
 */
?>
<nav class="game_version_segments" aria-label="{{ __('view_common.layout.header.game_versions') }}">
    <ul class="game_version_segments_list">
        @foreach ($allGameVersions as $gameVersion)
            <?php $isSelectedGameVersion = $currentUserGameVersion->id === $gameVersion->id; ?>
            <li>
                <a @class(['game_version_segment', 'border-accent' => $isSelectedGameVersion])
                   href="{{ route('gameversion.update', ['gameVersion' => $gameVersion]) }}"
                   data-current="{{ $isSelectedGameVersion ? 'true' : 'false' }}"
                   @if($isSelectedGameVersion) aria-current="true" @endif>
                    <img class="game_version_segment_logo"
                         src="{{ ksgAssetImage(sprintf('gameversions/%s.webp', $gameVersion->key)) }}" alt=""
                         height="16"/>
                    <span class="game_version_segment_name">{{ __($gameVersion->name) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
