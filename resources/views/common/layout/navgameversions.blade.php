<?php
/**
 * @var \Illuminate\Support\Collection|\App\Models\GameVersion\GameVersion[] $allGameVersions
 * @var \App\Models\GameVersion\GameVersion $currentUserGameVersion
 */
?>
<li class="nav-item dropdown d-none d-lg-block">
    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @include('common.gameversion.gameversion', ['gameVersion' => $currentUserGameVersion])
    </a>
    <div class="dropdown-menu text-center text-lg-left" aria-labelledby="languageDropdown">
        @foreach ($allGameVersions as $gameVersion)
            <a class="dropdown-item {{ $currentUserGameVersion->id === $gameVersion->id ? 'active' : '' }}"
               href="{{ route('gameversion.update', ['gameVersion' => $gameVersion]) }}">
                @include('common.gameversion.gameversion', ['gameVersion' => $gameVersion, 'width' => 50, 'showName' => true])
            </a>
        @endforeach
    </div>
</li>
