class CommonMapsViewsidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;
        this.sidebar.activate();

        $('#view_dungeonroute_group_setup').html(
            handlebarsGroupSetupParse(self.options.dungeonroute.setup)
        );

        $('#rating').barrating({
            theme: 'bars-1to10',
            readonly: true,
            initialRating: self.options.dungeonroute.avg_rating
        });

        $('#your_rating').barrating({
            theme: 'bars-1to10',
            deselectable: true,
            allowEmpty: true,
            onSelect: function (value, text, event) {
                self._rate(value);
            }
        });
        $('#favorite').bind('change', function (el) {
            self._favorite($('#favorite').is(':checked'));
        });

        $('#userreport_dungeonroute_modal_submit').bind('click', this._submitDungeonRouteUserReport.bind(this));

        refreshTooltips();
    }

    /**
     * Rates the current dungeon route or unset it.
     * @param value int
     */
    _rate(value) {
        let self = this;

        let isDelete = value === '';
        $.ajax({
            type: isDelete ? 'DELETE' : 'POST',
            url: '/ajax/' + self.options.dungeonroute.public_key + '/rate',
            dataType: 'json',
            data: {
                rating: value
            },
            success: function (json) {
                // Update the new average rating
                $('#rating').barrating('set', Math.round(json.new_avg_rating));
            }
        });
    }

    /**
     * Favorites the current dungeon route, or not.
     * @param value bool
     */
    _favorite(value) {
        let self = this;

        $.ajax({
            type: !value ? 'DELETE' : 'POST',
            url: '/ajax/' + self.options.dungeonroute.public_key + '/favorite',
            dataType: 'json',
            success: function (json) {

            }
        });
    }

    /**
     *
     * @private
     */
    _submitDungeonRouteUserReport() {
        $.ajax({
            type: 'POST',
            url: `/ajax/userreport/dungeonroute/${getState().getDungeonRoute().publicKey}`,
            dataType: 'json',
            data: {
                category: $('#dungeonroute_report_category').val(),
                username: $('#dungeonroute_report_username').val(),
                message: $('#dungeonroute_report_message').val(),
                contact_ok: $('#dungeonroute_report_contact_ok').is(':checked') ? 1 : 0
            },
            beforeSend: function () {
                $('#userreport_dungeonroute_modal_submit').hide();
                $('#userreport_dungeonroute_modal_saving').show();
            },
            success: function (json) {
                $('#userreport_dungeonroute_modal').modal('hide');
                showSuccessNotification(lang.get('messages.dungeonroute_report_enemy_success'));
            },
            complete: function () {
                $('#userreport_dungeonroute_modal_submit').show();
                $('#userreport_dungeonroute_modal_saving').hide();
            }
        });
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}