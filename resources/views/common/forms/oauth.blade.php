<?php
/** @var $allRegions \Illuminate\Support\Collection<\App\Models\GameServerRegion> */
?>
<div class="form-group">
    <div class="row">
        <div class="col">
            <img alt="Battle.net" src="{{ url('/images/oauth/battlenet_logo.png') }}" class="mx-auto d-block"/>
        </div>
    </div>
</div>
<div class="form-group">
    <div class="row no-gutters">
        @foreach($allRegions as $region)
            <div class="col">
                <a href="{{ route('login.battlenet', ['region' => $region->short]) }}"
                   class="btn btn-bnet mx-auto d-block border-left border-secondary">
                    {{ __($region->name) }}
                </a>
            </div>
        @endforeach
    </div>
</div>
<hr>

<div class="form-group">
    <div class="row">
        <div class="col">
            <a href="{{ route('login.discord') }}">
                <img alt="Discord" src="{{ url('/images/oauth/discord_logo.png') }}" class="mx-auto d-block"
                     style="max-height: 64px;"/>
            </a>
        </div>
    </div>
</div>

<hr>

<div class="form-group">
    <div class="row">
        <div class="col">
            <a href="{{ route('login.google') }}">
                <div class="google_login_image mx-auto d-block"></div>
            </a>
        </div>
    </div>
</div>
