/**
 * @typedef {Object} ProfileEditOptions
 * @property {string} userReportsTableSelector
 * @property {string} adFreeGiveawayTableSelector
 * @property {string} adFreeGiveawayCheckboxSelector
 * @property {string} echoColorSelector
 * @property {string} classColorSelector
 */

/**
 * @property {ProfileEditOptions} options
 */
class ProfileEdit extends InlineCode {

    activate() {
        super.activate();

        let self = this;

        let $classColors = $(this.options.classColorSelector);
        $classColors.unbind('click').bind('click', function () {
            $(self.options.echoColorSelector).val($(this).data('color'));
        });

        $(this.options.userReportsTableSelector).DataTable({
            'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {})
        });

        $(this.options.adFreeGiveawayTableSelector).on('draw.dt', function (e, settings, json, xhr) {
            $(self.options.adFreeGiveawayCheckboxSelector).unbind('change').bind('change', function () {
                let $this = $(this);
                let userPublicKey = $this.data('publickey');
                let isChecked = $this.is(':checked');

                $.ajax({
                    type: isChecked ? 'POST' : 'DELETE',
                    url: `/ajax/profile/adfree/${userPublicKey}`,
                    dataType: 'json',
                    success: function (json) {
                        showSuccessNotification(isChecked ?
                            lang.get('js.ad_free_giveaway_add_success') :
                            lang.get('js.ad_free_giveaway_remove_success')
                        );
                    },
                    error: function (xhr, textStatus, errorThrown) {
                        // Revert the checkbox
                        $this.prop('checked', !isChecked);

                        defaultAjaxErrorFn(xhr, textStatus, errorThrown);
                    }
                });
            });
        }).DataTable({
            'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {})
        });
    }
}
