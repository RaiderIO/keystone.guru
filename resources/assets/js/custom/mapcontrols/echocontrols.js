class EchoControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        console.assert(map instanceof DungeonMap, 'map is not DungeonMap', map);

        let self = this;

        this._mapControl = null;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_controls_route_echo_template'];

                let data = getHandlebarsDefaultVariables();

                // Build the status bar from the template
                self.domElement = $(template(data));
                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };

        this._setStatus('connecting');

        // This will probably not trigger the first time around, but it will trigger upon reconnect
        window.Echo.connector.socket.on('connect', function () {
            self._setStatus('connected');
        });

        // Whenever disconnected..
        window.Echo.connector.socket.on('disconnect', function () {
            self._setStatus('connecting');
        });

        // Keep track of the current users in this channel
        window.Echo.join('route-edit.' + this.map.getDungeonRoute().publicKey)
            .here(users => {
                for (let index in users) {
                    if (users.hasOwnProperty(index)) {
                        self._addUser(users[index]);
                    }
                }
                // Will probably initially set the connected state
                self._setStatus('connected');
            })
            .joining(user => {
                self._addUser(user);
            })
            .leaving(user => {
                self._removeUser(user);
            })
            .listen('.user-color-changed', (e) => {
                self._setUserColor(e.name, e.color);
            });
    }

    /**
     * Sets the status of the controls.
     * @param status string Either 'connecting' or 'connected'.
     * @private
     */
    _setStatus(status) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        let $connecting = $('.connecting');
        let $connected = $('.connected');
        switch (status) {
            case 'connecting':
                $connecting.show();
                $connected.hide();
                break;
            case 'connected':
                $connecting.hide();
                $connected.show();
                break;
        }
    }

    /**
     * Adds a user to the status bar.
     * @param user Object
     * @private
     */
    _addUser(user) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        let template = Handlebars.templates['map_controls_route_echo_member_template'];

        // May be unset when not our own user, but this confuses handlebars
        user.self = user.name === this.map.options.username;

        let data = getHandlebarsDefaultVariables();

        let result = template($.extend(data, user));
        $('#edit_route_echo_members_container').append(
            result
        );

        refreshTooltips();
    }

    /**
     * Removes a user from the status bar.
     * @param user Object
     * @private
     */
    _removeUser(user) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        // Remove element
        $('.echo_user_' + user.name).remove();
    }

    /**
     * Sets the display color of a user.
     * @param name string
     * @param color string
     * @private
     */
    _setUserColor(name, color) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        $('.echo_user_' + name).css('background-color', color);
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend(this.mapControlOptions);

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        this._mapControl = L.control.statusbar({position: 'tophorizontalcenter'}).addTo(this.map.leafletMap);

        // Add the leaflet draw control to the sidebar
        let container = this._mapControl.getContainer();
        $(container).removeClass('leaflet-control');
        let $targetContainer = $('#route_echo_container');
        $targetContainer.append(container);
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
    }
}