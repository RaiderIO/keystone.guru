@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('head')
    @parent

    <style>
        #settings_toggle {
            cursor: pointer;
            border: #d3e0e9 solid 1px;

            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
        }

        #map {
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        $(function () {
            let $settings = $('#settings');
            $settings.on('hide.bs.collapse', function (e) {
                let $caret = $("#settings_caret");
                $caret.removeClass('fa-caret-up');
                $caret.addClass('fa-caret-down');
            });

            $settings.on('show.bs.collapse', function (e) {
                let $caret = $("#settings_caret");
                $caret.removeClass('fa-caret-down');
                $caret.addClass('fa-caret-up');
            });

            $("#save_settings").bind('click', _saveSettings);
        });

        function _saveSettings() {
            $.ajax({
                type: 'POST',
                url: '{{ route('api.dungeonroute.update', $model->id) }}',
                dataType: 'json',
                data: {
                    faction: $("#faction").val(),
                    race:
                        $(".raceselect select").map(function () {
                            return $(this).val();
                        }).get()
                    ,
                    class:
                        $(".raceselect select").map(function () {
                            return $(this).val();
                        }).get()
                    ,
                    _method: 'PATCH'
                },
                success: function (json) {
                    console.log(json);
                }
            });
        }
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset

    @isset($model)
        <div class="col-lg-12">
            <div id="map_container col-lg-12">
                @include('common.maps.map', [
                // Use findMany rather than findOrFail; we need a collection in this parameter
                    'dungeons' => \App\Models\Dungeon::findMany([$model->dungeon_id]),
                    'dungeonSelect' => false
                ])
            </div>

            <div id="settings_toggle" class="col-lg-12 text-center btn btn-default" data-toggle="collapse"
                 data-target="#settings">
                <h4>
                    <i class="fa fa-cog"></i> {{ __('Settings') }} <i id="settings_caret" class="fa fa-caret-down"></i>
                </h4>
            </div>

            <div id="settings" class="col-lg-12 collapse">
                @include('common.group.composition', ['dungeonroute' => $model])

                <div class="form-group">
                    <div id="save_settings" class="col-lg-offset-5 col-lg-2 btn btn-success">
                        <i class="fa fa-save"></i> {{ __('Save settings') }}
                    </div>
                </div>
            </div>

        </div>
    @endisset

    {!! Form::close() !!}
@endsection

