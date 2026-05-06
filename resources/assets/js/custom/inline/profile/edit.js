class ProfileEdit extends InlineCode {


    activate() {
        super.activate();

        let $classColors = $('.profile_class_color');
        $classColors.unbind('click').bind('click', function () {
            $('#echo_color').val($(this).data('color'));
        });

        $('#profile_user_reports_table').DataTable({
            'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

            })
        });

        let dataTable = $('#profile_ad_free_giveaway_table').DataTable({
            'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

            })
        });

        dataTable.on('draw.dt', function (e, settings, json, xhr) {
            $('.ad_free_giveaway_checkbox').unbind('change').bind('change', function () {
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
        });
    }
}
