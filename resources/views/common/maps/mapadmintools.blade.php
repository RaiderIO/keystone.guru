@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent
    <script>
        $(function () {
            $('#admin_add_enemy_pack').bind('click', function () {
                mapObj.editTools.startPolygon()
                adminShowStopAction(true);
            });
            $('#admin_add_enemy_to_pack').bind('click', function () {
                adminShowStopAction(true);
            });

            $('#admin_stop_action').bind('click', function () {
                mapObj.editTools.stopDrawing();

                adminShowStopAction(false);
            })
        });

        function adminShowStopAction(show) {
            if (show) {
                $('#admin_stop_action').show();
            } else {
                $('#admin_stop_action').hide();
            }
        }
    </script>
@endsection

<div id="map-controls" class="col-md-2">
    <div class="panel panel-default">
        <div class="panel-heading">{{ __("Map controls") }}</div>
        <div class="panel-body">
            <div class="form-group">
                {!! Form::button('<i class="fas fa-ban"></i> ' . __('Stop action'), ['id' => 'admin_stop_action', 'class' => 'btn btn-danger', 'style' => 'display: none;']) !!}
            </div>
            <div>
                {{ __("Enemies") }}
            </div>
            <div class="form-group">
                {!! Form::button('<i class="fas fa-plus"></i> ' . __('Add enemy pack'), ['id' => 'admin_add_enemy_pack', 'class' => 'btn btn-success']) !!}
            </div>
            <div class="form-group">
                {!! Form::button('<i class="fas fa-plus"></i> ' .__('Add enemy to pack'), ['id' => 'admin_add_enemy_to_pack', 'class' => 'btn btn-success']) !!}
            </div>
        </div>
    </div>
</div>