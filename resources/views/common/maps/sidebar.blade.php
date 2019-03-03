@php($isMobile = (new \Jenssegers\Agent\Agent())->isMobile())
@section('scripts')
    @parent

    <script>
        let _switchDungeonFloorSelect = "#map_floor_selection";

        $(function () {
            // Make sure that the select options have a valid value
            _refreshFloorSelect();

            @isset($selectedFloorId)
            $(_switchDungeonFloorSelect).val({{$selectedFloorId}});
            @endisset

            $('#sidebarToggle').on('click', function () {
                // Dismiss
                if ($('#sidebar').hasClass('active')) {
                    _hideSidebar();
                }
                // Show
                else {
                    _showSidebar();
                }

                refreshTooltips();
            });

            $("#sidebar").mCustomScrollbar({
                theme: "minimal"
            });
        });

        function _hideSidebar() {
            let $sidebar = $('#sidebar');
            let $sidebarToggle = $('#sidebarToggle');
            // Save, cancel buttons when activating one of the draw buttons
            let $drawActions = $('.leaflet-draw-actions-top');

            // Hide sidebar
            $sidebar.removeClass('active');
            // Move toggle button back
            $sidebarToggle.removeClass('active');
            $sidebarToggle.attr('title', "{{ __('Expand the sidebar') }}");
            // Toggle image
            $sidebarToggle.find('i').removeClass('fa-arrow-left').addClass('fa-arrow-right');
        }

        function _showSidebar() {
            let $sidebar = $('#sidebar');
            let $sidebarToggle = $('#sidebarToggle');
            // Save, cancel buttons when activating one of the draw buttons
            let $drawActions = $('.leaflet-draw-actions-top');

            // Open sidebar
            $sidebar.addClass('active');
            // Move toggle button
            $sidebarToggle.addClass('active');
            $sidebarToggle.attr('title', "{{ __('Collapse the sidebar') }}");
            // Toggle image
            $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');
        }

        /**
         * Refreshes the floor select and fills it with the floors that fit the currently selected dungeon.
         * @private
         */
        function _refreshFloorSelect() {
            let $switchDungeonFloorSelect = $(_switchDungeonFloorSelect);
            if ($switchDungeonFloorSelect.is('select')) {
                // Clear of all options
                $switchDungeonFloorSelect.find('option').remove();
                // Add each new floor to the select
                $.each(_dungeonData.floors, function (index, floor) {
                    // Reconstruct the dungeon floor select
                    $switchDungeonFloorSelect.append($('<option>', {
                        text: floor.name,
                        value: floor.id
                    }));
                });

                refreshSelectPickers();
            }
        }
    </script>
@endsection

<div id="sidebarToggle" class="{{ $isMobile ? '' : 'active' }}" data-toggle="tooltip"
     title="{{ __('Collapse the sidebar') }}">
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