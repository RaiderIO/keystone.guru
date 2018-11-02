@section('scripts')
    @parent

    <script>
        $(function () {
            $('#sidebarToggle').on('click', function () {
                let $sidebar = $('#sidebar');
                let $sidebarToggle = $('#sidebarToggle');
                // Save, cancel buttons when activating one of the draw buttons
                let $drawActions = $('.leaflet-draw-actions-top');

                // Dismiss
                if ($sidebar.hasClass('active')) {
                    // Hide sidebar
                    $sidebar.removeClass('active');
                    // Move toggle button back
                    $sidebarToggle.removeClass('active');
                    $sidebarToggle.attr('title', "{{ __('Expand the sidebar') }}");
                    // Move draw actions (inverted from other buttons since we can't add active class by default easily)
                    $drawActions.addClass('inactive');
                    // Toggle image
                    $sidebarToggle.find('i').removeClass('fa-arrow-left').addClass('fa-arrow-right');
                }
                // Show
                else {
                    // Open sidebar
                    $sidebar.addClass('active');
                    // Move toggle button
                    $sidebarToggle.addClass('active');
                    $sidebarToggle.attr('title', "{{ __('Collapse the sidebar') }}");
                    // Move draw actions
                    $drawActions.removeClass('inactive');
                    // Toggle image
                    $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');

                    $('.collapse.in').toggleClass('in');
                    $('a[aria-expanded=true]').attr('aria-expanded', 'false');
                }

                refreshTooltips();
            });

            $("#sidebar").mCustomScrollbar({
                theme: "minimal"
            });
        });
    </script>
@endsection

<div id="sidebarToggle" class="active" data-toggle="tooltip" title="{{ __('Collapse the sidebar') }}">
    <i class="fas fa-arrow-left"></i>
</div>

<!-- Sidebar -->
<nav id="sidebar" class="active">
    <div class="sidebar-header">
        <h4>{!! $header !!}</h4>
        <div style="min-height: 25px;">
            @isset($subHeader)
                {!! $subHeader !!}
            @else
                &nbsp;
            @endisset
        </div>
        <span>
            <a href="{{ route('home') }}"><i class="fas fa-arrow-left"></i> Back to Keystone.guru</a>
        </span>
    </div>

    <div class="sidebar-content">
        <div class="container">
            @yield('sidebar-content')
        </div>
    </div>
</nav>