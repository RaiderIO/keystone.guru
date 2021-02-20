class SettingsTabMap extends SettingsTab {

    constructor(map, options) {
        super(map, options);

    }

    activate() {
        // Setup line weight
        let $weight = $('#edit_route_freedraw_options_weight');
        $weight.bind('change', function (changeEvent) {
            let weight = $('#edit_route_freedraw_options_weight :selected').val();

            c.map.polyline.defaultWeight = weight;

            Cookies.set('polyline_default_weight', weight);

            getState().getDungeonMap().refreshPather();
        });

        // -1 for value to index conversion
        $weight.val(c.map.polyline.defaultWeight);
    }
}