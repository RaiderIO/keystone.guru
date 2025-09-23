<div class="card-header">
    <h4>
        {{ __('view_common.modal.userreport.enemy.report_enemy_bug') }}
    </h4>
</div>
<div class="card-body">
    {{ html()->hidden('enemy_report_category', 'enemy')->id('enemy_report_category')->class('form-control') }}
    {{ html()->hidden('enemy_report_enemy_id', -1)->id('enemy_report_enemy_id')->class('form-control') }}
    @guest
        <div class="form-group">
            {{ html()->label(__('view_common.modal.userreport.enemy.your_name'), 'enemy_report_username') }}
            {{ html()->text('enemy_report_username')->id('enemy_report_username')->class('form-control') }}
        </div>
    @endguest
    <div class="form-group">
        {{ html()->label(__('view_common.modal.userreport.enemy.what_is_wrong'), 'enemy_report_message') }}
        {{ html()->textarea('enemy_report_message')->id('enemy_report_message')->class('form-control')->cols('50')->rows('10') }}
    </div>

    <div class="form-group">
        @guest
            {{ html()->label(__('view_common.modal.userreport.enemy.contact_by_email_guest'), 'enemy_report_contact_ok') }}
        @else
            {{ html()->label(__('view_common.modal.userreport.enemy.contact_by_email'), 'enemy_report_contact_ok') }}
        @endguest
        {{ html()->checkbox('enemy_report_contact_ok', false, 1)->class('form-control left_checkbox') }}
    </div>

    <button id="userreport_enemy_modal_submit" class="btn btn-info">
        {{ __('view_common.modal.userreport.enemy.submit') }}
    </button>
    <button id="userreport_enemy_modal_saving" class="btn btn-info disabled"
            style="display: none;">
        <i class="fas fa-circle-notch fa-spin"></i>
    </button>
</div>
