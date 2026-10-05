<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute|null $dungeonroute
 **/

$dungeonroute ??= null;
$publicKey    = $dungeonroute !== null ? $dungeonroute->public_key : 'auto';
?>

@auth
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
            <div class="mb-3">
                {{ html()->label(__('view_common.modal.userreport.dungeonroute.why_report_this_route'), 'dungeonroute_report_message') }}
                {{ html()->textarea('dungeonroute_report_message')->class('form-control dungeonroute_report_message')->cols('50')->rows('10') }}
            </div>

            <div class="mb-3">
                <div class="form-check">
                    {{ html()->checkbox('dungeonroute_report_contact_ok', false, 1)->class('form-check-input dungeonroute_report_contact_ok') }}
                    {{ html()->label(__('view_common.modal.userreport.dungeonroute.contact_by_email'), 'dungeonroute_report_contact_ok')->class('form-check-label') }}
                </div>
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
@else
    {{-- Route cards are cached for guests and users alike, so their Report entry always opens
         this modal - a guest gets the login prompt here instead of the report form --}}
    @include('common.general.inline', ['path' => 'common/forms/modalswap', 'modal' => '#userreport_dungeonroute_modal', 'options' => [
        'linkSelector' => '#userreport_dungeonroute_login_link',
        'fromModal'    => '#userreport_dungeonroute_modal',
        'toModal'      => '#login_modal',
    ]])
    <div class="report_route_login">
        <div class="card-header">
            <h4>
                {{ __('view_common.modal.userreport.dungeonroute.report_route') }}
            </h4>
        </div>
        <div class="card-body">
            <p>
                {{ __('view_common.modal.userreport.dungeonroute.login_to_report') }}
            </p>
            <a id="userreport_dungeonroute_login_link" class="btn btn-primary" href="{{ route('login') }}">
                <i class="fas fa-sign-in-alt"></i> {{ __('view_common.modal.userreport.dungeonroute.login') }}
            </a>
        </div>
    </div>
@endauth
