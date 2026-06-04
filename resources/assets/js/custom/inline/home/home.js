/**
 * @typedef {Object} HomeHomeOptions
 * @property {string} dungeon_select_id
 * @property {string} demo_routes_iframe_id
 * @property {Object} demo_route_mapping
 * @property {string} demoLoaderSelector
 */

/**
 * @property {HomeHomeOptions} options
 */
class HomeHome extends InlineCode {
    activate() {
        super.activate();

        let self = this;

        $(this.options.dungeon_select_id).bind('change', function () {
            let dungeonId = $(this).val();

            $(self.options.demo_routes_iframe_id).attr('src', `/${self.options.demo_route_mapping[dungeonId]}`);
            $(self.options.demoLoaderSelector).show();
        });

        $(self.options.demo_routes_iframe_id).on('load', function () {
            $(self.options.demoLoaderSelector).hide();
        });
    }
}
