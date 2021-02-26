<?php
/** @var string $anchor */

$selectedFloorId = isset($selectedFloorId) ? $selectedFloorId : 0;
$edit = isset($edit) ? $edit : false;
$oppositeAnchor = $anchor === 'left' ? 'right' : 'left';
?>
<div id="{{ $id }}Toggle" class="sidebar-toggle anchor-{{$anchor}} {{ $isMobile ? '' : 'active' }}"
     data-toggle="tooltip">
    <i class="fas fa-arrow-{{ $isMobile ? $oppositeAnchor : $anchor }}"></i>
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
                <a class="sidebar-background" href="{{ route('home') }}"><i class="fas fa-arrow-{{ $isMobile ? $oppositeAnchor : $anchor }}"></i> {{ __('Back to Keystone.guru') }}</a>
            </div>
        @endisset
    </div>
    @hasSection('sidebar-sticky')
        @yield('sidebar-sticky')
    @endif

    <div class="sidebar-content {{ $edit ? 'edit' : '' }}" data-simplebar
    @hasSection('sidebar-sticky')
        style="padding: 0 !important"
    @endif
    >
        <div class="container">
            {{ $slot }}
        </div>
    </div>
</nav>