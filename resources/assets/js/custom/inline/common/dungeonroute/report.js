class CommonDungeonrouteReport extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();
        let self = this;

        // Make the user report modal actually do something
        $(this.options.selectorRoot).find('.dungeonroute_report_submit').unbind('click').bind('click', this._submitDungeonRouteUserReport.bind(this));

        if (this.options.publicKey === 'auto') {
            // Attempt to find the public key from the element that we clicked
            $('#userreport_dungeonroute_modal').on('show.bs.modal', function (showEvent) {
                self.options.publicKey = $(showEvent.relatedTarget).data('publickey');

                // If we're still undefined, let us know since it won't work then
                if (typeof self.options.publicKey === 'undefined') {
                    console.error(`Unable to find public key of dungeonroute on relatedTarget!`);
                }
            });
        }
    }

    /**
     *
     * @private
     */
    _submitDungeonRouteUserReport() {
        let $root = $(this.options.selectorRoot);

        $.ajax({
            type: 'POST',
            url: `/ajax/userreport/dungeonroute/${this.options.publicKey}`,
            dataType: 'json',
            data: {
                category: $root.find('.dungeonroute_report_category').val(),
                username: $root.find('.dungeonroute_report_username').val(),
                message: $root.find('.dungeonroute_report_message').val(),
                contact_ok: $root.find('.dungeonroute_report_contact_ok').is(':checked') ? 1 : 0
            },
            beforeSend: function () {
                $root.find('.dungeonroute_report_submit').hide();
                $root.find('.dungeonroute_report_saving').show();
            },
            success: function (json) {
                $('#userreport_dungeonroute_modal').modal('hide');
                showSuccessNotification(lang.get('messages.dungeonroute_report_enemy_success'));
            },
            complete: function () {
                $root.find('.dungeonroute_report_submit').show();
                $root.find('.dungeonroute_report_saving').hide();
            }
        });
    }
}
