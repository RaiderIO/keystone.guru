<div class="card-header">
    <h4>
        {{ __('Report enemy bug') }}
    </h4>
</div>
<div class="card-body">
    {!! Form::hidden('enemy_report_category', 'enemy', ['id' => 'enemy_report_category', 'class' => 'form-control']) !!}
    {!! Form::hidden('enemy_report_enemy_id', -1, ['id' => 'enemy_report_enemy_id', 'class' => 'form-control']) !!}
    @guest
        <div class="form-group">
            {!! Form::label('enemy_report_username', __('Your name')) !!}
            {!! Form::text('enemy_report_username', null, ['id' => 'enemy_report_username', 'class' => 'form-control']) !!}
        </div>
    @endguest
    <div class="form-group">
        {!! Form::label('enemy_report_message', sprintf(__('What\'s wrong with this enemy? (max. 1000 characters)'), $model->name)) !!}
        {!! Form::textarea('enemy_report_message', null, ['id' => 'enemy_report_message', 'class' => 'form-control', 'cols' => '50', 'rows' => '10']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('enemy_report_contact_ok', __('Contact me by e-mail if required for further investigation')) !!}
        {!! Form::checkbox('enemy_report_contact_ok', 1, false, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <button id="userreport_enemy_modal_submit" class="btn btn-info">
        {{ __('Submit') }}
    </button>
    <button id="userreport_enemy_modal_saving" class="btn btn-info disabled"
            style="display: none;">
        <i class="fas fa-circle-notch fa-spin"></i>
    </button>
</div>