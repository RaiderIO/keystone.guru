class AdminNpcEdit extends InlineCode {

    /**
     *
     */
    activate() {
        let self = this;

        let $input = $(this.options.scaledHealthLevelSelector)
            .val(Cookies.get('npc_edit_key_level', '15'))
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 2,
                max: 40,
                onFinish: function (data) {
                    Cookies.set('npc_edit_key_level', $input.val(), cookieDefaultAttributes);
                }
            });

        // Restore previously set fortified/tyrannical values
        $(this.options.scaledHealthTypeSelector)
            .val(Cookies.get('npc_edit_scaled_type', 'none'))
            .on('change', function () {
                Cookies.set('npc_edit_scaled_type', $(this).val(), cookieDefaultAttributes);
            });

        $(this.options.scaledHealthToBaseHealthApplyBtnSelector).on('click', function () {
            let scaledHealth = parseInt($(self.options.scaledHealthSelector).val().replaceAll(',', ''));

            if (scaledHealth <= 0) {
                return;
            }

            let percentage = $(self.options.scaledHealthPercentageSelector).val() || 100;

            $(self.options.baseHealthSelector).val(
                c.map.enemy.calculateBaseHealthForKey(
                    (scaledHealth / percentage) * 100, $input.val(), self._isFortified(), self._isTyrannical()
                )
            );
        });
    }

    /**
     *
     * @returns {boolean}
     * @private
     */
    _isFortified() {
        return $(this.options.scaledHealthTypeSelector).val() === 'fortified';
    }

    /**
     *
     * @returns {boolean}
     * @private
     */
    _isTyrannical() {
        return $(this.options.scaledHealthTypeSelector).val() === 'tyrannical';
    }
}
