<div id="enemy_details_modal_title" class="card-header">
    <div class="row">
        <div class="col d-flex align-items-center">
            <h4 id="enemy_details_modal_title_text" class="mb-0">
                {{-- Filled by JS --}}
            </h4>
        </div>
        <div class="col-auto">
            <div class="card-header p-0 border-0" id="enemy_report_heading">
                <button class="btn btn-warning w-100 collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#enemy_report_collapse" aria-expanded="false"
                        aria-controls="enemy_report_collapse">
                    <i class="fa fa-bug" data-bs-toggle="tooltip"
                       title="{{ __('view_common.modal.enemydetails.report_an_issue') }}"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="card-body">
    <div id="enemy_details_modal_body">
        {{-- Filled by JS --}}
    </div>

    <div class="accordion mt-3" id="enemy_details_accordion">
        <div class="card">
            <div id="enemy_report_collapse" class="collapse" aria-labelledby="enemy_report_heading"
                 data-bs-parent="#enemy_details_accordion">
                <div class="card-body p-0 mt-3">
                    {{ html()->hidden('enemy_report_category', 'enemy')->id('enemy_report_category')->class('form-control') }}
                    {{ html()->hidden('enemy_report_enemy_id', -1)->id('enemy_report_enemy_id')->class('form-control') }}
                    @guest
                        <div class="mb-3">
                            {{ html()->label(__('view_common.modal.userreport.enemy.your_name'), 'enemy_report_username') }}
                            {{ html()->text('enemy_report_username')->id('enemy_report_username')->class('form-control') }}
                        </div>
                    @endguest
                    <div class="mb-3">
                        {{ html()->label(__('view_common.modal.userreport.enemy.what_is_wrong'), 'enemy_report_message') }}
                        {{ html()->textarea('enemy_report_message')->id('enemy_report_message')->class('form-control')->cols('50')->rows('10') }}
                    </div>

                    <div class="mb-3">
                        @guest
                            {{ html()->label(__('view_common.modal.userreport.enemy.contact_by_email_guest'), 'enemy_report_contact_ok') }}
                        @else
                            {{ html()->label(__('view_common.modal.userreport.enemy.contact_by_email'), 'enemy_report_contact_ok') }}
                        @endguest
                        {{ html()->checkbox('enemy_report_contact_ok', false, 1)->class('form-control left_checkbox') }}
                    </div>

                    <button id="userreport_enemy_modal_submit" class="btn btn-info w-100">
                        {{ __('view_common.modal.userreport.enemy.submit') }}
                    </button>
                    <button id="userreport_enemy_modal_saving" class="btn btn-info disabled w-100"
                            style="display: none;">
                        <i class="fas fa-circle-notch fa-spin"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
