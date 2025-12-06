class CommonDungeonMappingversion extends InlineCode {

    activate() {

        // Save settings in the modal
        $('#save_mapping_version').unbind('click').bind('click', this._saveMappingVersion);
    }

    /**
     *
     * @private
     */
    _saveMappingVersion() {
        $.ajax({
            type: 'POST',
            url: `/ajax/admin/mappingVersion/${getState().getMapContext().getMappingVersion().id}`,
            dataType: 'json',
            data: {
                game_version_id: $('#map_mapping_version_game_version_id').val(),
                facade_enabled: $('#map_mapping_version_facade_enabled').is(':checked') ? 1 : 0,
                enemy_forces_required: $('#map_mapping_version_enemy_forces_required').val(),
                enemy_forces_required_teeming: $('#map_mapping_version_enemy_forces_required_teeming').val(),
                enemy_forces_shrouded: $('#map_mapping_version_enemy_forces_shrouded').val(),
                enemy_forces_shrouded_zul_gamux: $('#map_mapping_version_enemy_forces_shrouded_zul_gamux').val(),
                timer_max_seconds: $('#map_mapping_version_timer_max_seconds').val(),
                timer_max_minutes: $('#map_mapping_version_timer_max_minutes').val(),

                _method: 'PATCH'
            },
            beforeSend: function () {
                $('#save_mapping_version').hide();
                $('#save_mapping_version_saving').show();
            },
            success: function () {
                showSuccessNotification(lang.get('js.mapping_version_saved'));
            },
            complete: function () {
                $('#save_mapping_version').show();
                $('#save_mapping_version_saving').hide();
            }
        });
    }
}
