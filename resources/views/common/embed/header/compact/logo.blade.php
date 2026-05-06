<div class="col-auto text-right pr-1">
    <div class="row no-gutters align-items-center" style="height: 36px;">
        <div class="col">
            <!-- Logo + text: md and up -->
            <a href="{{ route('home') }}" target="_blank" class="d-none d-md-inline-block">
                <img src="{{ ksgAssetImage('logo/logo_and_text.png') }}"
                     class="header_embed_compact_logo_and_text"
                     alt="{{ config('app.name') }}">
            </a>

            <!-- Logo only: below md -->
            <a href="{{ route('home') }}" target="_blank" class="d-inline-block d-md-none">
                <img src="{{ ksgAssetImage('logo/logo.png') }}"
                     class="header_embed_compact_logo"
                     alt="{{ config('app.name') }}">
            </a>
        </div>
    </div>
</div>
