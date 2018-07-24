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

            $(".selectpicker").selectpicker({
                showIcon: true
            });

            // Add icons to the faction dropdown
            $.each($("#faction option"), function (index, value) {
                let faction = _factions[index];
                let html = $("#template_faction_dropdown_icon").html();
                html = html.replace('src=""', 'src="../../images/' + faction.iconfile.path + '"')
                    .replace('placeholder', faction.name.toLowerCase())
                    .replace('{text}', faction.name);
                $(value).data('content', html);
            });
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
                        $(".classselect select").map(function () {
                            return $(this).val();
                        }).get()
                    ,
                    affixes: $("#affixes").val(),
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

            <div id="settings_toggle" class="col-lg-12 col-xs-12 text-center btn btn-default" data-toggle="collapse"
                 data-target="#settings">
                <h4>
                    <i class="fa fa-cog"></i> {{ __('Settings') }} <i id="settings_caret" class="fa fa-caret-down"></i>
                </h4>
            </div>

            <div id="settings" class="col-lg-12 collapse">
                <h3>
                    {{ __('Group composition') }}
                </h3>

                @include('common.group.composition', ['dungeonroute' => $model])

                <h3>
                    {{ __('Affixes (optional)') }}
                </h3>

                <div class="container">
                    @include('common.group.affixes')
                </div>

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

