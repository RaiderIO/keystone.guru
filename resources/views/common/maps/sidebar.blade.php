<?php
$isMobile = (new \Jenssegers\Agent\Agent())->isMobile();
$selectedFloorId = isset($selectedFloorId) ? $selectedFloorId : 0;
?>
<div id="{{ $id }}Toggle" class="sidebar-toggle anchor-{{$anchor}} {{ $isMobile ? '' : 'active' }}"
     data-toggle="tooltip">
    <i class="fas fa-arrow-{{ $isMobile ? 'right' : 'left' }}"></i>
</div>

<!-- Sidebar -->
<nav id="{{ $id }}" class="sidebar anchor-{{$anchor}} {{ $isMobile ? '' : 'active' }}">
    <div class="sidebar-header" style="background-image: url('/images/dungeons/{{$dungeon->expansion->shortname}}/{{$dungeon->key}}.jpg'); background-size: cover;">
        <h4 title="{!! $header !!}" data-toggle="tooltip">{!! $header !!}</h4>
        @isset($customSubHeader)
            {!! $customSubHeader !!}
        @else
            <div class="sidebar-header-subtitle">
                @isset($subHeader)
                    {!! $subHeader !!}
                @endisset
            </div>
            <div >
                <a class="sidebar-background" href="{{ route('home') }}"><i class="fas fa-arrow-{{ $anchor }}"></i> {{ __('Back to Keystone.guru') }}</a>
            </div>
        @endisset
    </div>

    <div class="sidebar-content" data-simplebar>
        <div class="container">
            {{ $slot }}
        </div>
    </div>
</nav>