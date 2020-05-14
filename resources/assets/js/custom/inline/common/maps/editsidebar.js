class CommonMapsEditsidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);
        this.sidebar.activate();

        this._colorPicker = null;
    }

    /**
     *
     */
    activate() {
        let self = this;

        // Copy to clipboard functionality
        $('#map_copy_to_clipboard').bind('click', function () {
            // https://codepen.io/shaikmaqsood/pen/XmydxJ
            let $temp = $("<input>");
            $("body").append($temp);
            $temp.val($('#map_shareable_link').val()).select();
            document.execCommand("copy");
            $temp.remove();

            showInfoNotification(lang.get('messages.copied_to_clipboard'));
        });

        $(this.options.switchDungeonFloorSelect).change(function () {
            // Pass the new floor ID to the map
            getState().setFloorId($(self.options.switchDungeonFloorSelect).val());
            getState().getDungeonMap().refreshLeafletMap();
        });

        // Setup line weight
        let $weight = $('#edit_route_freedraw_options_weight');
        $weight.bind('change', function (changeEvent) {
            let weight = $('#edit_route_freedraw_options_weight :selected').val();

            c.map.polyline.defaultWeight = weight;

            Cookies.set('polyline_default_weight', weight);

            self.map.refreshPather();

            self.addControl();
        });
        // -1 for value to index conversion
        $weight.val(c.map.polyline.defaultWeight - 1);

        // Setup color picker
        // Handle changes
        this._colorPicker = Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
            el: `#edit_route_freedraw_options_color`,
            default: c.map.polyline.defaultColor
        })).on('save', (color, instance) => {
            color = '#' + color.toHEXA().join('');

            c.map.path.defaultColor = color;
            c.map.polyline.defaultColor = color;
            c.map.killzone.polylineOptions.color = color;
            c.map.killzone.polygonOptions.color = color;

            Cookies.set('polyline_default_color', color);

            getState().getDungeonMap().refreshPather();

            // Reset ourselves
            instance.hide();
        });

        // Add a class to make it display properly
        $(`.view_dungeonroute_details_row .pickr .pcr-button`).addClass('h-100 w-100');

        $('#edit_route_freedraw_options_color').bind('click', function () {
            self._colorPicker.show();
        });
    }
}