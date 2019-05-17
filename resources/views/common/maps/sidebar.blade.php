<?php
$isMobile = (new \Jenssegers\Agent\Agent())->isMobile();
$selectedFloorId = isset($selectedFloorId) ? $selectedFloorId : 0;
?>
@include('common.general.inline', ['path' => 'common/maps/sidebar'])
@section('scripts')
    @parent

    <script>
        let _switchDungeonFloorSelect = "#map_floor_selection";
        let defaultSelectedFloorId = {{ $selectedFloorId }};
    </script>
@endsection

<div id="sidebarToggle" class="{{ $isMobile ? '' : 'active' }}" data-toggle="tooltip">
    <i class="fas fa-arrow-{{ $isMobile ? 'right' : 'left' }}"></i>
</div>

<!-- Sidebar -->
<nav id="sidebar" class="{{ $isMobile ? '' : 'active' }}">
    <div class="sidebar-header">
        <h4 title="{!! $header !!}" data-toggle="tooltip">{!! $header !!}</h4>
        <div class="sidebar-header-subtitle">
            @isset($subHeader)
                {!! $subHeader !!}
            @endisset
        </div>
        <span>
            <a href="{{ route('home') }}"><i class="fas fa-arrow-left"></i> {{ __('Back to Keystone.guru') }}</a>
        </span>
    </div>

    <div class="sidebar-content">
        <div class="container">
            @yield('sidebar-content')
        </div>
    </div>
</nav>