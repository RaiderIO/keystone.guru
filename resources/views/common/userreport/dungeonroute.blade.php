<div class="card-header">
    <h4>
        {{ __('Report route') }}
    </h4>
</div>
<div class="card-body">
    {!! Form::hidden('dungeonroute_report_category', 'enemy', ['id' => 'dungeonroute_report_category', 'class' => 'form-control']) !!}
    {!! Form::hidden('dungeonroute_report_enemy_id', -1, ['id' => 'dungeonroute_report_enemy_id', 'class' => 'form-control']) !!}
    @guest
        <div class="form-group">
            {!! Form::label('dungeonroute_report_username', __('Your name')) !!}
            {!! Form::text('dungeonroute_report_username', null, ['id' => 'dungeonroute_report_username', 'class' => 'form-control']) !!}
        </div>
    @endguest
    <div class="form-group">
        {!! Form::label('dungeonroute_report_message', sprintf(__('Why do you want to report this route? (max. 1000 characters)'), $model->name)) !!}
        {!! Form::textarea('dungeonroute_report_message', null, ['id' => 'dungeonroute_report_message', 'class' => 'form-control', 'cols' => '50', 'rows' => '10']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('dungeonroute_report_contact_ok', __('Contact me by e-mail if required for further investigation')) !!}
        {!! Form::checkbox('dungeonroute_report_contact_ok', 1, false, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <button id="userreport_dungeonroute_modal_submit" class="btn btn-info">
        {{ __('Submit') }}
    </button>
    <button id="userreport_dungeonroute_modal_saving" class="btn btn-info disabled"
            style="display: none;">
        <i class="fas fa-circle-notch fa-spin"></i>
    </button>
</div>