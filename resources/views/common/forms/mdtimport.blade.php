@include('common.general.inline', ['path' => 'common/forms/mdtimport', 'options' => [
    'temporaryRouteSelector' => '#mdt_import_sandbox',
    'mdtImportTeamIdSelector' => '#mdt_import_team_id_select',
]])

{{ html()->form('POST', route('dungeonroute.new.mdtimport'))->open() }}
<div class="form-group">
    <div class="row mb-2">
        <div class="col">
            {{ html()->label(__('view_common.forms.mdtimport.paste_mdt_export_string') . '<span class="form-required">*</span>', 'import_string') }}
        </div>
        <div class="col-auto import_mdt_string_reset_btn" style="display: none;">
            <div class="btn btn-outline-warning" data-toggle="tooltip"
                 title="{{ __('view_common.forms.mdtimport.reset_title') }}">
                <i class="fas fa-undo"></i>
            </div>
        </div>
    </div>
    {{ html()->textarea('import_string_textarea', '')->class('form-control import_mdt_string_textarea')->data('simplebar', '') }}
    {{ html()->hidden('import_string', '')->class('import_string') }}
</div>
@guest
    <div class="form-group">
        <div class="text-info">
            <i class="fas fa-info-circle"></i> {{ sprintf(
                    __('view_common.forms.mdtimport.unregistered_user_all_routes_temporary'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )
                }}
        </div>
    </div>
    {{ html()->hidden('mdt_import_sandbox', 1) }}
@else
    <div class="form-group">
        <label for="mdt_import_sandbox">
            {{ __('view_common.forms.mdtimport.temporary_route') }}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                sprintf(
                    __('view_common.forms.mdtimport.temporary_route_title'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                )
                 }}"></i>
        </label>
        {{ html()->checkbox('mdt_import_sandbox', false, 1)->id('mdt_import_sandbox')->class('form-control left_checkbox') }}
    </div>
    @include('common.team.select', ['id' => 'mdt_import_team_id_select',  'required' => false])
@endguest
<div class="form-group">
    <div class="bg-info p-1 import_mdt_string_loader" style="display: none;">
        <?php /* I'm Dutch, of course the loading indicator is a stroopwafel */ ?>
        <i class="fas fa-stroopwafel fa-spin"></i> {{ __('view_common.forms.mdtimport.parsing_your_string') }}
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

<div class="form-group">
    <div class="mdt_string_errors">

    </div>
</div>

<div class="form-group">
    <div class="row">
        <div class="col">
            <div class="assign_notes_to_pulls_container">
                <label for="assign_notes_to_pulls">
                    {{ __('view_common.forms.mdtimport.assign_notes_to_pulls') }}
                </label>
                {{ html()->checkbox('assign_notes_to_pulls', true, 1)->id('assign_notes_to_pulls')->class('form-control left_checkbox') }}
            </div>
        </div>
        <div class="col">
            <div class="import_as_this_week_container" style="display: none;">
                <label for="import_as_this_week">
                    {{ __('view_common.forms.mdtimport.import_as_this_week') }}
                </label>
                {{ html()->checkbox('import_as_this_week', false, 1)->id('import_as_this_week')->class('form-control left_checkbox') }}
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    {{ html()->input('submit')->value(__('view_common.forms.mdtimport.import_route'))->class('btn btn-primary col-md-auto')->disabled() }}
    <div class="col-md">

    </div>
</div>
{{ html()->form()->close() }}
