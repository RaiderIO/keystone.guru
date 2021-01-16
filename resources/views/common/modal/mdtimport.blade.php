{{ Form::open(['route' => 'dungeonroute.new.mdtimport']) }}
<h3>
    {{ __('Import from MDT string') }}
</h3>
<div class="form-group">
    {!! Form::label('import_string', __('Paste your Mythic Dungeon Tools export string')) !!}
    {{ Form::textarea('import_string_textarea', '', ['class' => 'form-control import_mdt_string_textarea', 'data-simplebar' => '']) }}
    {{ Form::hidden('import_string', '', ['class' => 'import_string']) }}
</div>
<div class="form-group">
    <div class="bg-info p-1 import_mdt_string_loader" style="display: none;">
        <?php /* I'm Dutch, of course the loading indicator is a stroopwafel */ ?>
        <i class="fas fa-stroopwafel fa-spin"></i> {{ __('Parsing your string...') }}
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
    {!! Form::hidden('sandbox', 0, ['class' => 'hidden_sandbox']) !!}
    {!! Form::submit(__('Import'), ['class' => 'btn btn-primary col-md-auto', 'disabled']) !!}
    <div class="col-md">

    </div>
</div>
{{ Form::close() }}