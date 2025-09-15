<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute|null $dungeonroute
 **/

$dungeonroute ??= null;
$publicKey    = $dungeonroute?->public_key ?? 'auto';
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/report', 'options' => [
    'selectorRoot' => sprintf('.report_route_%s', $publicKey),
    'publicKey' => $publicKey
]])
<div class="report_route_{{ $publicKey }}">
    <div class="card-header">
        <h4>
            {{ __('view_common.modal.userreport.dungeonroute.report_route') }}
        </h4>
    </div>
    <div class="card-body">
        {{ html()->hidden('dungeonroute_report_category', 'enemy')->class('form-control dungeonroute_report_category') }}
        {{ html()->hidden('dungeonroute_report_enemy_id', -1)->class('form-control dungeonroute_report_enemy_id') }}
        @guest
            <div class="form-group">
                {{ html()->label(__('view_common.modal.userreport.dungeonroute.your_name'), 'dungeonroute_report_username') }}
                {{ html()->text('dungeonroute_report_username')->class('form-control dungeonroute_report_username') }}
            </div>
        @endguest
        <div class="form-group">
            {{ html()->label(__('view_common.modal.userreport.dungeonroute.why_report_this_route'), 'dungeonroute_report_message') }}
            {{ html()->textarea('dungeonroute_report_message')->class('form-control dungeonroute_report_message')->cols('50')->rows('10') }}
        </div>

        <div class="form-group">

            @guest
                {{ html()->label(__('view_common.modal.userreport.dungeonroute.contact_by_email_guest'), 'dungeonroute_report_contact_ok') }}
            @else
                {{ html()->label(__('view_common.modal.userreport.dungeonroute.contact_by_email'), 'dungeonroute_report_contact_ok') }}
            @endguest
            {{ html()->checkbox('dungeonroute_report_contact_ok', false, 1)->class('form-control left_checkbox dungeonroute_report_contact_ok') }}
        </div>

        <button class="btn btn-info dungeonroute_report_submit">
            {{ __('view_common.modal.userreport.dungeonroute.submit') }}
        </button>
        <button class="btn btn-info dungeonroute_report_saving disabled"
                style="display: none;">
            <i class="fas fa-circle-notch fa-spin"></i>
        </button>
    </div>
</div>
