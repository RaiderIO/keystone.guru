<?php
$show = isset($show) ? $show : [];
?>
@section('scripts')
    @parent

    <script>
        $(function () {
            // Copy to clipboard functionality
            $('#map_copy_to_clipboard').bind('click', function () {
                // https://codepen.io/shaikmaqsood/pen/XmydxJ
                let $temp = $("<input>");
                $("body").append($temp);
                $temp.val($('#map_shareable_link').val()).select();
                document.execCommand("copy");
                $temp.remove();

                addFixedFooterInfo("{{ __('Copied to clipboard') }}", 2000);
            });

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
                    // Move draw actions
                    $drawActions.removeClass('inactive');
                    // Toggle image
                    $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');

                    $('.collapse.in').toggleClass('in');
                    $('a[aria-expanded=true]').attr('aria-expanded', 'false');
                }
            });

            $("#sidebar").mCustomScrollbar({
                theme: "minimal"
            });
        });
    </script>
@endsection

<div id="sidebarToggle" class="active" data-toggle="tooltip" title="{{ __('Expand the sidebar') }}">
    <i class="fas fa-arrow-left"></i>
</div>
<!-- Sidebar -->
<nav id="sidebar" class="active">
    <div class="sidebar-header">
        <h3>{{ __('Toolbox') }}</h3>
    </div>

    <div class="sidebar-content">
        <div class="container">
        @isset($show['virtual-tour'])
            <!-- Virtual tour -->
                <div class="form-group">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ __('First time?') }}
                            </h5>
                            <div id="start_virtual_tour" class="btn btn-info col">
                                <i class="fas fa-info-circle"></i> {{ __('Start virtual tour') }}
                            </div>
                        </div>
                    </div>
                </div>
        @endisset

        <!-- Edit route -->
            <div class="form-group">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Edit route') }}</h5>
                        <!-- Draw controls are injected here through drawcontrols.js -->
                        <div id="edit_route_draw_container" class="row">

                        </div>
                    </div>
                </div>
            </div>

            <!-- Visibility -->
            <div class="form-group">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Visibility') }}</h5>
                        <div class="row">
                            <div id="map_enemy_visuals_container" class="col">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @isset($show['shareable-link'])
            <!-- Shareable link -->
                <div class="form-group">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Shareable link') }}</h5>
                            <div class="row">
                                <div class="col">
                                    {!! Form::text('map_shareable_link', route('dungeonroute.view', ['dungeonroute' => $model->public_key]),
                                    ['id' => 'map_shareable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col mt-2">
                                    {!! Form::button('<i class="far fa-copy"></i> ' . __('Copy to clipboard'), ['id' => 'map_copy_to_clipboard', 'class' => 'btn btn-info col-md']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        @endisset

        @isset($show['route-settings'])
            <!-- Route settings -->
                <div class="form-group">
                    <div class="btn btn-primary col" data-toggle="modal" data-target="#settings_modal">
                        <i class='fas fa-cog'></i> {{ __('Route settings') }}
                    </div>
                </div>
        @endisset

        @isset($show['route-publish'])
            <!-- Published state -->
                <div class="form-group">
                    <div class="row">
                        <div class="col">
                            <div id="map_route_publish"
                                 class="btn btn-success col-md {{ $model->published === 1 ? 'd-none' : '' }}">
                                <i class="fa fa-check-circle"></i> {{ __('Publish route') }}
                            </div>
                            <div id="map_route_unpublish"
                                 class="btn btn-warning col-md {{ $model->published === 0 ? 'd-none' : '' }}">
                                <i class="fa fa-times-circle"></i> {{ __('Unpublish route') }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div id="map_route_unpublished_info"
                                 class="alert alert-info text-center {{ $model->published === 1 ? 'd-none' : '' }}">
                                <i class="fa fa-info-circle"></i> {{ __('Your route is currently unpublished. Nobody can view your route until you publish it.') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endisset

            @isset($show['no-modifications-warning'])
                <div class="form-group">
                    <div class="alert alert-warning text-center">
                        <i class="fa fa-exclamation-triangle"></i> {{ __('Warning! Any modification you make in tryout mode will not be saved!') }}
                    </div>
                </div>
            @endisset
        </div>
    </div>
</nav>