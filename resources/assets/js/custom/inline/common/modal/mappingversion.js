/**
 @typedef {Object} CommonModalMappingversionOptions
 @property {string} saveMappingVersionSelector
 @property {string} saveMappingVersionSavingSelector
 @property {string} gameVersionIdSelector
 @property {string} facadeEnabledSelector
 @property {string} enemyForcesRequiredSelector
 @property {string} enemyForcesRequiredTeemingSelector
 @property {string} enemyForcesShroudedSelector
 @property {string} enemyForcesShroudedZulGamuxSelector
 @property {string} timerMaxSecondsSelector
 @property {string} timerMaxMinutesSelector
 @property {Number} lng
 @property {Number} floor_id
 */

/**
 * @property {CommonModalMappingversionOptions} options
 */
class CommonModalMappingversion extends InlineCode {

    activate() {
        // Save settings in the modal
        $(this.options.saveMappingVersionSelector).unbind('click').bind('click', this._saveMappingVersion.bind(this));
    }

    /**
     *
     * @private
     */
    _saveMappingVersion() {
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/admin/mappingVersion/${getState().getMapContext().getMappingVersion().id}`,
            dataType: 'json',
            data: {
                game_version_id: $(self.options.gameVersionIdSelector).val(),
                facade_enabled: $(self.options.facadeEnabledSelector).is(':checked') ? 1 : 0,
                enemy_forces_required: $(self.options.enemyForcesRequiredSelector).val(),
                enemy_forces_required_teeming: $(self.options.enemyForcesRequiredTeemingSelector).val(),
                enemy_forces_shrouded: $(self.options.enemyForcesShroudedSelector).val(),
                enemy_forces_shrouded_zul_gamux: $(self.options.enemyForcesShroudedZulGamuxSelector).val(),
                timer_max_seconds: $(self.options.timerMaxSecondsSelector).val(),
                timer_max_minutes: $(self.options.timerMaxMinutesSelector).val(),

                _method: 'PATCH'
            },
            beforeSend: function () {
                $(self.options.saveMappingVersionSelector).hide();
                $(self.options.saveMappingVersionSavingSelector).show();
            },
            success: function () {
                showSuccessNotification(lang.get('js.mapping_version_saved'));
            },
            complete: function () {
                $(self.options.saveMappingVersionSelector).show();
                $(self.options.saveMappingVersionSavingSelector).hide();
            }
        });
    }
}
