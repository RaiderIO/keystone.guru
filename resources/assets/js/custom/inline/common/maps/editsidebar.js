let grapick = null;

class CommonMapsEditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);

        this._colorPicker = null;
        this._grapick = null;
    }

    /**
     *
     */
    activate() {
        this.sidebar.activate();

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

        // Gradient setup
        this._grapick = new Grapick({el: '#edit_route_freedraw_options_gradient'});

        // Restore pull_gradient if set
        let handlers = this._getHandlersFromCookie();
        for (let index in handlers) {
            if (handlers.hasOwnProperty(index)) {
                this._grapick.addHandler(handlers[index][0], handlers[index][1]);
            }
        }

        grapick = this._grapick;

        // Do stuff on change of the gradient
        this._grapick.on('change', complete => {
            Cookies.set('pull_gradient', self._grapick.getColorValue());
        });

        $('#edit_route_freedraw_options_gradient_apply_to_pulls').bind('click', function () {
            let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            let count = killZoneMapObjectGroup.objects.length
            for (let index in killZoneMapObjectGroup.objects) {
                if (killZoneMapObjectGroup.objects.hasOwnProperty(index)) {

                    let killZone = killZoneMapObjectGroup.objects[index];

                    // Prevent division by 0
                    killZone.color = pickHexFromHandlers(self._getHandlersFromCookie(), count === 1 ? 50 : (index / (count - 1)) * 100);
                    killZone.setSynced(true);
                    killZone.save();
                }
            }
        });
    }

    /**
     * Fetches a handler structure from a cookie
     * @returns {[]}
     * @private
     */
    _getHandlersFromCookie() {
        let result = [];

        let pullGradient = Cookies.get('pull_gradient');
        if (typeof pullGradient !== 'undefined') {
            let handlers = pullGradient.split(',');
            for (let index in handlers) {
                if (handlers.hasOwnProperty(index)) {
                    let handler = handlers[index];
                    let values = handler.trim().split(' ');
                    // console.log(('' + values[1]), parseInt(('' + values[1]).replace('%', '')), values[0]);
                    result.push([parseInt(('' + values[1]).replace('%', '')), values[0]]);
                }
            }
        } else {
            result.push([0, 'red'], 100, 'blue');
        }

        return result;
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}