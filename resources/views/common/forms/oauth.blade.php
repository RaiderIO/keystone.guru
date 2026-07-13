<?php

use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, GameServerRegion> $allRegions
 */
?>
<div class="mb-3">
    <div class="row">
        <div class="col">
            <img alt="Battle.net" src="{{ ksgAssetImage('oauth/battlenet_logo.png') }}" class="mx-auto d-block"/>
        </div>
    </div>
</div>
<div class="mb-3">
    <div class="row g-0">
        @foreach($allRegions as $region)
            <div class="col">
                <a href="{{ route('login.battlenet', ['region' => $region->short]) }}"
                   class="btn btn-bnet mx-auto d-block border-start border-secondary">
                    {{ __($region->name) }}
                </a>
            </div>
        @endforeach
    </div>
</div>
<hr>

<div class="mb-3">
    <div class="row">
        <div class="col">
            <a href="{{ route('login.discord') }}">
                <img alt="Discord" src="{{ ksgAssetImage('oauth/discord_logo.png') }}" class="mx-auto d-block"
                     style="max-height: 64px;"/>
            </a>
        </div>
    </div>
</div>

<hr>

<div class="mb-3">
    <div class="row">
        <div class="col">
            <a href="{{ route('login.google') }}">
                <div class="google_login_image mx-auto d-block"></div>
            </a>
        </div>
    </div>
</div>
