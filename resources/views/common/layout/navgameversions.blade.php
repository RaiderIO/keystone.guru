<?php
$user = Auth::user();
// Default local or user locale
$currentUserGameVersion     = \App\Models\GameVersion\GameVersion::getUserOrDefaultGameVersion();
?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @include('common.gameversion.gameversion', ['gameVersion' => $currentUserGameVersion])
    </a>
    <div class="dropdown-menu text-center text-lg-left" aria-labelledby="languageDropdown">
        @foreach (\App\Models\GameVersion\GameVersion::ALL as $key => $id)
            <a class="dropdown-item {{ $currentUserGameVersion->id === $id ? 'active' : '' }}"
               href="#">
                @include('common.gameversion.gameversion', ['gameVersion' => $currentUserGameVersion])
            </a>
        @endforeach
    </div>
</li>
