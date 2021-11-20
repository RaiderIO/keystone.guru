<div class="form-group">
    <div class="row">
        <div class="col">
            <img src="{{ url('/images/oauth/battlenet_logo.png') }}" class="mx-auto d-block"/>
        </div>
    </div>
</div>
<div class="form-group">
    <div class="row no-gutters">
        @foreach(\App\Models\GameServerRegion::all() as $region)
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
                <img src="{{ url('/images/oauth/discord_logo.png') }}" class="mx-auto d-block"
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
                <img class="google_login_image mx-auto d-block"/>
            </a>
        </div>
    </div>
</div>
