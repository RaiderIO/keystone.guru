class CommonMapsViewsidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);
    }

    /**
     *
     */
    activate() {
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
                self.rate(value);
            }
        });
        $('#favorite').bind('change', function (el) {
            self.favorite($('#favorite').is(':checked'));
        });

        refreshTooltips();
    }

    /**
     * Rates the current dungeon route or unset it.
     * @param value int
     */
    rate(value) {
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
    favorite(value) {
        let self = this;

        $.ajax({
            type: !value ? 'DELETE' : 'POST',
            url: '/ajax/' + self.options.dungeonroute.public_key + '/favorite',
            dataType: 'json',
            success: function (json) {

            }
        });
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}