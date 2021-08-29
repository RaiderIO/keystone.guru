<?php
/** @var \App\Models\DungeonRoute|null $dungeonroute */
$dungeonroute = $dungeonroute ?? null;
$publicKey = optional($dungeonroute)->public_key ?? 'auto';
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/report', 'options' => [
    'selectorRoot' => sprintf('.report_route_%s', $publicKey),
    'publicKey' => $publicKey
]])
<div class="report_route_{{ $publicKey }}">
    <div class="card-header">
        <h4>
            {{ __('views/common.modal.userreport.dungeonroute.report_route') }}
        </h4>
    </div>
    <div class="card-body">
        {!! Form::hidden('dungeonroute_report_category', 'enemy', ['class' => 'form-control dungeonroute_report_category']) !!}
        {!! Form::hidden('dungeonroute_report_enemy_id', -1, ['class' => 'form-control dungeonroute_report_enemy_id']) !!}
        @guest
            <div class="form-group">
                {!! Form::label('dungeonroute_report_username', __('views/common.modal.userreport.dungeonroute.your_name')) !!}
                {!! Form::text('dungeonroute_report_username', null, ['class' => 'form-control dungeonroute_report_username']) !!}
            </div>
        @endguest
        <div class="form-group">
            {!! Form::label('dungeonroute_report_message', __('views/common.modal.userreport.dungeonroute.why_report_this_route')) !!}
            {!! Form::textarea('dungeonroute_report_message', null, ['class' => 'form-control dungeonroute_report_message', 'cols' => '50', 'rows' => '10']) !!}
        </div>

        <div class="form-group">

        @guest
        {!! Form::label('dungeonroute_report_contact_ok', __('views/common.modal.userreport.dungeonroute.contact_by_email_guest')) !!}
        @else
        {!! Form::label('dungeonroute_report_contact_ok', __('views/common.modal.userreport.dungeonroute.contact_by_email')) !!}
        @endguest
            {!! Form::checkbox('dungeonroute_report_contact_ok', 1, false, ['class' => 'form-control left_checkbox dungeonroute_report_contact_ok']) !!}
        </div>

        <button class="btn btn-info dungeonroute_report_submit">
            {{ __('views/common.modal.userreport.dungeonroute.submit') }}
        </button>
        <button class="btn btn-info dungeonroute_report_saving disabled"
                style="display: none;">
            <i class="fas fa-circle-notch fa-spin"></i>
        </button>
    </div>
</div>