<?php
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * This is only visible for mobile users
 *
 * @var Collection<GameVersion> $allGameVersions
 * @var GameVersion             $currentUserGameVersion
 */
?>
<li class="nav-item dropdown d-lg-none d-block">
    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @include('common.gameversion.gameversionnav', ['gameVersion' => $currentUserGameVersion])
    </a>
    <div class="dropdown-menu text-center text-lg-left" aria-labelledby="languageDropdown">
        @foreach ($allGameVersions as $gameVersion)
            <a class="dropdown-item {{ $currentUserGameVersion->id === $gameVersion->id ? 'active' : '' }}"
               href="{{ route('gameversion.update', ['gameVersion' => $gameVersion]) }}">
                @include('common.gameversion.gameversionnav', ['gameVersion' => $gameVersion, 'width' => 50, 'showName' => true])
            </a>
        @endforeach
    </div>
</li>
