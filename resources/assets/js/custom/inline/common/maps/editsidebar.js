class CommonMapsEditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);
        this.settingsTabMap = new SettingsTabMap(options);
        this.settingsTabPull = new SettingsTabPull(options);

        this._grapick = null;

        getState().register('focusedenemy:changed', this, this._onFocusedEnemyChanged.bind(this));
    }

    /**
     *
     */
    activate() {
        super.activate();

        this.sidebar.activate();
        this.settingsTabMap.activate();
        this.settingsTabPull.activate();

        $('#userreport_enemy_modal_submit').bind('click', this._submitEnemyUserReport.bind(this));
    }

    /**
     * Called when the focused enemy was changed
     * @param focusedEnemyChangedEvent
     * @private
     */
    _onFocusedEnemyChanged(focusedEnemyChangedEvent) {
        let focusedEnemy = focusedEnemyChangedEvent.data.focusedenemy;
        let isNull = focusedEnemy === null;
        // Show/hide based on being set or not
        // $('#enemy_info_container').toggle(!isNull);
        if (!isNull) {
            let visualData = focusedEnemy.getVisualData();
            if (visualData !== null) {
                $('#enemy_info_container').show().find('.card-title').html(focusedEnemy.npc.name);

                // Update the focused enemy in the sidebar
                let template = Handlebars.templates['map_sidebar_enemy_info_template'];

                $('#enemy_info_key_value_container').html(
                    template(visualData)
                );
                $('#enemy_report_enemy_id').val(focusedEnemy.id);
            }
        }
    }

    /**
     *
     * @private
     */
    _submitEnemyUserReport() {
        let enemyId = $('#enemy_report_enemy_id').val();

        $.ajax({
            type: 'POST',
            url: `/ajax/userreport/enemy/${enemyId}`,
            dataType: 'json',
            data: {
                category: $('#enemy_report_category').val(),
                username: $('#enemy_report_username').val(),
                message: $('#enemy_report_message').val(),
                contact_ok: $('#enemy_report_contact_ok').is(':checked') ? 1 : 0
            },
            beforeSend: function () {
                $('#userreport_enemy_modal_submit').hide();
                $('#userreport_enemy_modal_saving').show();
            },
            success: function (json) {
                $('#userreport_enemy_modal').modal('hide');
                showSuccessNotification(lang.get('messages.user_report_enemy_success'));
            },
            complete: function () {
                $('#userreport_enemy_modal_submit').show();
                $('#userreport_enemy_modal_saving').hide();
            }
        });
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
        getState().unregister('focusedenemy:changed', this);
    }
}