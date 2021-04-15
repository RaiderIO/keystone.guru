class HomeHome extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

        $(this.options.dungeon_select_id).bind('change', function () {
            let dungeonId = $(this).val();

            $(self.options.demo_routes_iframe_id).attr('src', `/${self.options.demo_route_mapping[dungeonId]}`);
            $('.demo-loader').show();
        });

        $(self.options.demo_routes_iframe_id).on('load', function () {
            $('.demo-loader').hide();
        });
    }
}