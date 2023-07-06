@section('scripts')
    @parent

    <script>
        $(function () {
            let $temporaryRoute = $('#mdt_import_sandbox');
            $temporaryRoute.bind('change', function () {
                let $mdtImportTeamIdSelect = $('#mdt_import_team_id_select');

                if ($temporaryRoute.is(':checked')) {
                    $mdtImportTeamIdSelect.attr('disabled', true);
                } else {
                    $mdtImportTeamIdSelect.removeAttr('disabled');
                }

                refreshSelectPickers();
            });
        })
    </script>
@endsection

{{ Form::open(['route' => 'dungeonroute.new.mdtimport']) }}
<div class="form-group">
    <div class="row mb-2">
        <div class="col">
            {!! Form::label('import_string',
            __('views/common.forms.mdtimport.paste_mdt_export_string') . '<span class="form-required">*</span>', [], false)
            !!}
        </div>
        <div class="col-auto import_mdt_string_reset_btn" style="display: none;">
            <div class="btn btn-outline-warning" data-toggle="tooltip"
                 title="{{ __('views/common.forms.mdtimport.reset_title') }}">
                <i class="fas fa-undo"></i>
            </div>
        </div>
    </div>
    {{ Form::textarea('import_string_textarea', '', ['class' => 'form-control import_mdt_string_textarea', 'data-simplebar' => '']) }}
    {{ Form::hidden('import_string', '', ['class' => 'import_string']) }}
</div>
@guest
    <div class="form-group">
        <div class="text-info">
            <i class="fas fa-info-circle"></i> {{ sprintf(
                    __('views/common.forms.mdtimport.unregistered_user_all_routes_temporary'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )
                }}
        </div>
    </div>
    {!! Form::hidden('mdt_import_sandbox', 1) !!}
@else
    <div class="form-group">
        <label for="mdt_import_sandbox">
            {{ __('views/common.forms.mdtimport.temporary_route') }}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                sprintf(
                    __('views/common.forms.mdtimport.temporary_route_title'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                )
                 }}"></i>
        </label>
        {!! Form::checkbox('mdt_import_sandbox', 1, false, ['id' => 'mdt_import_sandbox', 'class' => 'form-control left_checkbox']) !!}
    </div>
    @include('common.team.select', ['id' => 'mdt_import_team_id_select',  'required' => false])
@endguest
<div class="form-group">
    <div class="bg-info p-1 import_mdt_string_loader" style="display: none;">
        <?php /* I'm Dutch, of course the loading indicator is a stroopwafel */ ?>
        <i class="fas fa-stroopwafel fa-spin"></i> {{ __('views/common.forms.mdtimport.parsing_your_string') }}
    </div>
</div>

<div class="form-group">
    <div class="import_mdt_string_details">

    </div>
</div>

<div class="form-group">
    <div class="mdt_string_warnings">

    </div>
</div>

<div class="form-group import_as_this_week_container" style="display: none;">
    <label for="import_as_this_week">
        {{ __('views/common.forms.mdtimport.import_as_this_week') }}
    </label>
    {!! Form::checkbox('import_as_this_week', 1, false, ['id' => 'import_as_this_week', 'class' => 'form-control left_checkbox']) !!}
</div>

<div class="form-group">
    {!! Form::submit(__('views/common.forms.mdtimport.import_route'), ['class' => 'btn btn-primary col-md-auto', 'disabled']) !!}
    <div class="col-md">

    </div>
</div>
{{ Form::close() }}
