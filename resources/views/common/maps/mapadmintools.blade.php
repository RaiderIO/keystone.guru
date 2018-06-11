@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent
    <script>
        $(function(){
            $('#admin_add_enemy_pack').bind('click', function(){

            });
            $('#admin_add_enemy_to_pack').bind('click', function(){

            });
        });
    </script>
@endsection

<div id="map-controls" class="col-md-2">
    <div class="panel panel-default">
        <div class="panel-heading">{{ __("Map controls") }}</div>
        <div class="panel-body">
            <div>
                {{ __("Enemies") }}
            </div>
            <div class="form-group">
                {!! Form::button('<i class="fa fa-plus"></i> ' . __('Add enemy pack'), ['id' => 'admin_add_enemy_pack', 'class' => 'btn btn-success']) !!}
            </div>
            <div class="form-group">
                {!! Form::button('<i class="fa fa-plus"></i> ' .__('Add enemy to pack'), ['id' => 'admin_add_enemy_to_pack', 'class' => 'btn btn-success']) !!}
            </div>
        </div>
    </div>
</div>