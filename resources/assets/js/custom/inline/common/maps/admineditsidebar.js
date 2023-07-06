class CommonMapsAdmineditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);

        getState().register('focusedenemy:changed', this, this._onFocusedEnemyChanged.bind(this));
    }

    /**
     *
     */
    activate() {
        super.activate();

        this.sidebar.activate();
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
                $('#enemy_info_container').show().find('.card-title').empty().append(
                    $('<a />').attr('href', `/admin/npc/${focusedEnemy.npc.id}`).text(focusedEnemy.npc.name)
                );

                // Update the focused enemy in the sidebar
                let template = Handlebars.templates['map_sidebar_enemy_info_template'];

                $('#enemy_info_key_value_container').html(
                    template(visualData)
                );

                refreshTooltips($('#enemy_info_container [data-toggle="tooltip"]'));
                $('#enemy_report_enemy_id').val(focusedEnemy.id);
            }
        }
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
        getState().unregister('focusedenemy:changed', this);
    }
}