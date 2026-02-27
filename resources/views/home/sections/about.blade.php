<?php
?>
<div class="container">
    <div class="row my-4">
        <div class="col-12">
            <h4>{{ __('view_home.sections.about.title') }}</h4>
        </div>
    </div>
    <div class="row my-4">
        <div class="col-auto">
            <img alt="Logo" src="{{ ksgAssetImage('logo/logo.png') }}" style="width: 80px;"/>
        </div>
        <div class="col">
            <h4>{{ __('view_home.sections.about.tagline') }}</h4>
            <p>
                {{ __('view_home.sections.about.tagline_description') }}
            </p>
        </div>
    </div>
</div>
