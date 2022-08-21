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
                    Cookies.set('npc_edit_key_level', $input.val());
                }
            });

        $(this.options.scaledHealthToBaseHealthApplyBtnSelector).on('click', function () {
            $(self.options.baseHealthSelector).val(0);
        });
    }
}
