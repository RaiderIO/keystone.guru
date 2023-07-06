<div class="card-header">
    <h4>
        {{ __('views/common.modal.userreport.enemy.report_enemy_bug') }}
    </h4>
</div>
<div class="card-body">
    {!! Form::hidden('enemy_report_category', 'enemy', ['id' => 'enemy_report_category', 'class' => 'form-control']) !!}
    {!! Form::hidden('enemy_report_enemy_id', -1, ['id' => 'enemy_report_enemy_id', 'class' => 'form-control']) !!}
    @guest
        <div class="form-group">
            {!! Form::label('enemy_report_username', __('views/common.modal.userreport.enemy.your_name')) !!}
            {!! Form::text('enemy_report_username', null, ['id' => 'enemy_report_username', 'class' => 'form-control']) !!}
        </div>
    @endguest
    <div class="form-group">
        {!! Form::label('enemy_report_message', __('views/common.modal.userreport.enemy.what_is_wrong')) !!}
        {!! Form::textarea('enemy_report_message', null, ['id' => 'enemy_report_message', 'class' => 'form-control', 'cols' => '50', 'rows' => '10']) !!}
    </div>

    <div class="form-group">
        @guest
            {!! Form::label('enemy_report_contact_ok', __('views/common.modal.userreport.enemy.contact_by_email_guest')) !!}
        @else
            {!! Form::label('enemy_report_contact_ok', __('views/common.modal.userreport.enemy.contact_by_email')) !!}
        @endguest
        {!! Form::checkbox('enemy_report_contact_ok', 1, false, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <button id="userreport_enemy_modal_submit" class="btn btn-info">
        {{ __('views/common.modal.userreport.enemy.submit') }}
    </button>
    <button id="userreport_enemy_modal_saving" class="btn btn-info disabled"
            style="display: none;">
        <i class="fas fa-circle-notch fa-spin"></i>
    </button>
</div>