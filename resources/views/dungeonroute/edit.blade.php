@extends('layouts.app', ['custom' => true, 'footer' => false, 'headerFloat' => true])
@section('header-title', $headerTitle)
@section('head')
    @parent

    <style>
        #settings_wrapper {
            border: #d3e0e9 solid 1px;

            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
        }

        #settings_toggle {
            cursor: pointer;
        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        $(function () {
            let $settings = $('#settings');
            $settings.on('hide.bs.collapse', function (e) {
                let $caret = $('#settings_caret');
                $caret.removeClass('fa-caret-up');
                $caret.addClass('fa-caret-down');
            });

            $settings.on('show.bs.collapse', function (e) {
                let $caret = $('#settings_caret');
                $caret.removeClass('fa-caret-down');
                $caret.addClass('fa-caret-up');
            });

            $('#save_settings').bind('click', _saveSettings);

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

            $('#map_route_publish').bind('click', function () {
                _setPublished(true);
            });

            $('#map_route_unpublish').bind('click', function () {
                _setPublished(false);
            });
        });

        function _setPublished(value) {
            $.ajax({
                type: 'POST',
                url: '{{ route('api.dungeonroute.publish', $model->public_key) }}',
                dataType: 'json',
                data: {
                    published: value === true ? 1 : 0
                },
                success: function (json) {
                    if (value) {
                        // Published
                        $('#map_route_publish').addClass('d-none');
                        $('#map_route_unpublish').removeClass('d-none');
                        $('#map_route_unpublished_info').addClass('d-none');

                        addFixedFooterSuccess("{{ __('Route published') }}");
                    } else {
                        // Unpublished
                        $('#map_route_publish').removeClass('d-none');
                        $('#map_route_unpublish').addClass('d-none');
                        $('#map_route_unpublished_info').removeClass('d-none');

                        addFixedFooterWarning("{{ __('Route unpublished') }}");
                    }
                }
            });
        }

        function _saveSettings() {
            $.ajax({
                type: 'POST',
                url: '{{ route('api.dungeonroute.update', $model->public_key) }}',
                dataType: 'json',
                data: {
                    dungeon_route_title: $('#dungeon_route_title').val(),
                    faction_id: $('#faction_id').val(),
                    specialization:
                        $('.specializationselect select').map(function () {
                            return $(this).val();
                        }).get()
                    ,
                    class:
                        $('.classselect select').map(function () {
                            return $(this).val();
                        }).get()
                    ,
                    race:
                        $('.raceselect select').map(function () {
                            return $(this).val();
                        }).get()
                    ,
                    unlisted: $('#unlisted').is(':checked') ? 1 : 0,
                    @if(Auth::user()->hasRole('admin'))
                    demo: $('#demo').is(':checked') ? 1 : 0,
                    @endif
                    affixes: $('#affixes').val(),
                    _method: 'PATCH'
                },
                beforeSend: function () {
                    $('#save_settings').hide();
                    $('#save_settings_saving').show();
                },
                success: function (json) {
                    addFixedFooterSuccess("{{__('Settings saved successfully')}}");
                },
                complete: function () {
                    $('#save_settings').show();
                    $('#save_settings_saving').hide();
                }
            });
        }

        $(document).ready(function () {
            $('#dismiss').on('click', function () {
            });

            $('#sidebarToggle').on('click', function () {
                let $sidebar = $('#sidebar');
                let $sidebarToggle = $('#sidebarToggle');
                // Dismiss
                if( $sidebar.hasClass('active') ){
                    // hide sidebar
                    $sidebar.removeClass('active');
                    // Move toggle button back
                    // $sidebarToggle.removeClass('active');
                    // Toggle image
                    $sidebarToggle.find('i').removeClass('fa-arrow-left').addClass('fa-arrow-right');
                }
                // Show
                else {
                    // open sidebar
                    $sidebar.addClass('active');
                    // Move toggle button
                    // $sidebarToggle.addClass('active');
                    // Toggle image
                    $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');

                    $('.collapse.in').toggleClass('in');
                    $('a[aria-expanded=true]').attr('aria-expanded', 'false');
                }
            });
        });
    </script>
@endsection

@section('content')

    @isset($model)
        {{--<div class="container p-0">--}}
        {{--<div class="form-group">--}}
        {{--<div class="row">--}}
        {{--<div class="col-md">--}}
        {{--<div id="map_route_unpublished_info"--}}
        {{--class="alert alert-info {{ $model->published === 1 ? 'd-none' : '' }}">--}}
        {{--<i class="fa fa-info-circle"></i> {{ __('Your route is currently unpublished. Nobody can view your route until you publish it.') }}--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--<div class="col-md-auto">--}}
        {{--<div id="map_route_publish"--}}
        {{--class="btn btn-success col-md {{ $model->published === 1 ? 'd-none' : '' }}">--}}
        {{--<i class="fa fa-check-circle"></i> {{ __('Publish route') }}--}}
        {{--</div>--}}
        {{--<div id="map_route_unpublish"--}}
        {{--class="btn btn-warning col-md {{ $model->published === 0 ? 'd-none' : '' }}">--}}
        {{--<i class="fa fa-times-circle"></i> {{ __('Unpublish route') }}--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--</div>--}}

        {{--<div class="form-group">--}}
        {{--{!! Form::label('map_shareable_link', __('Shareable link')) !!}--}}
        {{--<div class="row">--}}
        {{--<div class="col-md">--}}
        {{--{!! Form::text('map_shareable_link', route('dungeonroute.view', ['dungeonroute' => $model->public_key]),--}}
        {{--['id' => 'map_shareable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}--}}
        {{--</div>--}}
        {{--<div class="col-md-auto">--}}
        {{--{!! Form::button('<i class="far fa-copy"></i> ' . __('Copy to clipboard'), ['id' => 'map_copy_to_clipboard', 'class' => 'btn btn-info col-md']) !!}--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--</div>--}}
        <div class="wrapper">
            <!-- Sidebar -->
            <nav id="sidebar">
                <div class="sidebar-header">
                    <h3>{{ __('Toolbox') }}</h3>
                </div>
                <div id="sidebarToggle" title="{{ __('Expand the sidebar') }}">
                    <i class="fas fa-arrow-right"></i>
                </div>

                Sidebar content
            </nav>

            @include('common.maps.map', [
                'dungeon' => \App\Models\Dungeon::findOrFail($model->dungeon_id),
                'dungeonroute' => $model,
                'edit' => true
            ])
        </div>

        {{--<div id='settings_wrapper' class='container'>--}}
        {{--<div id='settings_toggle' class='col-lg-12 text-center btn btn-default' data-toggle='collapse'--}}
        {{--data-target='#settings'>--}}
        {{--<h4 class='mb-0'>--}}
        {{--<i class='fas fa-cog'></i> {{ __('Settings') }} <i id='settings_caret'--}}
        {{--class='fas fa-caret-down'></i>--}}
        {{--</h4>--}}
        {{--</div>--}}

        {{--<div id='settings' class='col-lg-12 collapse'>--}}
        {{--<h3>--}}
        {{--{{ __('General') }}--}}
        {{--</h3>--}}
        {{--<div class="form-group">--}}
        {{--{!! Form::label('dungeon_route_title', __('Title')) !!}--}}
        {{--{!! Form::text('dungeon_route_title', $model->title, ['class' => 'form-control']) !!}--}}
        {{--</div>--}}

        {{--<h3>--}}
        {{--{{ __('Group composition (optional)') }}--}}
        {{--</h3>--}}

        {{--@php($factions = $model->dungeon->isSiegeOfBoralus() ? \App\Models\Faction::where('name', '<>', 'Unspecified')->get() : null)--}}
        {{--@include('common.group.composition', ['dungeonroute' => $model, 'factions' => $factions])--}}

        {{--<h3 class='mt-1'>--}}
        {{--{{ __('Affixes (optional)') }}--}}
        {{--</h3>--}}

        {{--<div class='container mt-1'>--}}
        {{--{!! Form::checkbox('teeming', 1, $model->teeming, ['id' => 'teeming', 'class' => 'form-control left_checkbox d-none']) !!}--}}
        {{--@include('common.group.affixes', ['dungeonroute' => $model, 'teemingselector' => '#teeming'])--}}
        {{--</div>--}}

        {{--@if(Auth::user()->hasPaidTier('unlisted-routes') )--}}
        {{--<h3>--}}
        {{--{{ __('Sharing') }}--}}
        {{--</h3>--}}
        {{--<div class='form-group'>--}}
        {{--{!! Form::label('unlisted', __('Private (when checked, only people with the link can view your route)')) !!}--}}
        {{--{!! Form::checkbox('unlisted', 1, $model->unlisted, ['class' => 'form-control left_checkbox']) !!}--}}
        {{--</div>--}}
        {{--@endif--}}

        {{--@if(Auth::user()->hasRole('admin'))--}}
        {{--<h3>--}}
        {{--{{ __('Admin') }}--}}
        {{--</h3>--}}
        {{--<div class='form-group'>--}}
        {{--{!! Form::label('demo', __('Mark as demo route')) !!}--}}
        {{--{!! Form::checkbox('demo', 1, $model->demo, ['class' => 'form-control left_checkbox']) !!}--}}
        {{--</div>--}}
        {{--@endif--}}

        {{--<div class='form-group'>--}}
        {{--<div id='save_settings' class='offset-lg-5 col-lg-2 btn btn-success'>--}}
        {{--<i class='fas fa-save'></i> {{ __('Save settings') }}--}}
        {{--</div>--}}
        {{--<div id='save_settings_saving' class='offset-lg-5 col-lg-2 btn btn-success disabled'--}}
        {{--style='display: none;'>--}}
        {{--<i class='fas fa-circle-notch fa-spin'></i>--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--</div>--}}
        {{--</div>--}}
    @endisset
@endsection

