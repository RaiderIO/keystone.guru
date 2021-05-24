class EchoControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        console.assert(map instanceof DungeonMap, 'map is not DungeonMap', map);

        this._mapControl = null;

        let echo = getState().getEcho();
        echo.register('status:changed', this, this._onStatusChanged.bind(this));
        echo.register('user:add', this, this._onUserAdd.bind(this));
        echo.register('user:remove', this, this._onUserRemove.bind(this));
        echo.register('user:colorchanged', this, this._onUserColorChanged.bind(this));

        getState().register('echocursorsenabled:changed', this, this._onEchoCursorsEnabledChanged.bind(this));

        // Show/hide the cursors on the map based on existing value from cookie (if any)
        this._onEchoCursorsEnabledChanged();

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                return jQuery('<span>', {
                    text: 'Connecting...'
                })[0];
            }
        };
    }

    _onStatusChanged(statusChangedEvent) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        this._setStatus(statusChangedEvent.data.newStatus);
    }

    _onUserAdd(userAddEvent) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        this._addUser(userAddEvent.data.user);
    }

    _onUserRemove(userRemoveEvent) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        this._removeUser(userRemoveEvent.data.user);
    }

    _onUserColorChanged(userRemoveEvent) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        this._applyUserColor(userRemoveEvent.data.user);
    }

    _restoreExistingEchoState() {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        // Initial status while we wait for status changes
        let echo = getState().getEcho();
        this._setStatus(echo.getStatus());

        this._refreshVisual();

        // We can only add existing users at this point because that's when our control is fully built.
        let existingUsers = echo.getUsers();
        for (let i = 0; i < existingUsers.length; i++) {
            this._addUser(existingUsers[i], false);
        }
    }

    /**
     * Rebuilds the html of the header
     * @private
     */
    _refreshVisual() {
        let self = this;

        let template = Handlebars.templates['map_controls_route_echo_connected_users_template'];

        let result = template($.extend({}, getHandlebarsDefaultVariables(), {
            cursorsActive: getState().getEchoCursorsEnabled(),
            users: getState().getEcho().getUsers(),
            type: getState().getMapContext().getType(),
        }));

        $('#route_echo_container').html(result);
        $('#echo_toggle_cursors').on('click', function () {
            let nowActive = !$(this).hasClass('active');

            getState().setEchoCursorsEnabled(nowActive);

            // Rebuild the button so that the tooltip is correct
            self._refreshVisual();
        });

        refreshTooltips();
    }

    /**
     * Sets the status of the controls.
     * @param status string Either 'connecting' or 'connected'.
     * @private
     */
    _setStatus(status) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        let $connectedContainer = $('#echo_connected_container');

        switch (status) {
            case ECHO_STATUS_DISCONNECTED:
                $connectedContainer.removeClass('text-success').addClass('text-warning');
                break;
            case ECHO_STATUS_CONNECTED:
                $connectedContainer.removeClass('text-warning').addClass('text-success');
                break;
            default:
                console.error('Invalid echo state found!');
                break;
        }
    }

    /**
     * Adds a user to the status bar.
     * @param user Object
     * @param refreshVisual boolean
     * @private
     */
    _addUser(user, refreshVisual = true) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        if (refreshVisual) {
            this._refreshVisual();
        }

        // Update the color
        this._applyUserColor(user);
    }

    /**
     * Removes a user from the status bar.
     * @param user Object
     * @private
     */
    _removeUser(user) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
        // Remove elements associated with the user
        $(`.echo_user_${user.id}`).remove();
        $(`#style_echo_user_${user.id}`).remove();
    }

    /**
     * Sets the display color of a user.
     * @param user {object}
     * @private
     */
    _applyUserColor(user) {
        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);

        let styleID = `style_echo_user_${user.id}`;
        // Delete any previous styles
        $(`#${styleID}`).remove();

        // Gets funky here, create a new CSS class with the user's color so we can direct some elements to use this class
        $(`<style id="${styleID}">`)
            .prop('type', 'text/css')
            // Use getUserColor() function since it has failsafe for when the echo color is not set for some reason
            .html(`
            .echo_user_${user.id} {
                border: 3px ${getState().getEcho().getUserColor(user.id)} solid !important
            }`)
            .appendTo('head');

        // Update the text color depending on the luminance
        let $user = $(`.echo_user_${user.id}`);
        if (isColorDark(user.color)) {
            $user.addClass('text-white');
            $user.removeClass('text-dark');
        } else {
            $user.addClass('text-dark');
            $user.removeClass('text-white');
        }
    }

    /**
     * Called when the user decides to hide/show the cursors of others.
     * @private
     */
    _onEchoCursorsEnabledChanged() {
        let userMousePositionMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_USER_MOUSE_POSITION);

        userMousePositionMapObjectGroup.setVisibility(getState().getEchoCursorsEnabled());
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

        // In case the control was recreated by switching floors
        if (getState().getEcho().getUsers().length > 0) {
            this._restoreExistingEchoState();
        }
    }

    cleanup() {
        super.cleanup();

        let echo = getState().getEcho();
        echo.unregister('status:changed', this);
        echo.unregister('user:add', this);
        echo.unregister('user:remove', this);
        echo.unregister('user:colorchanged', this);

        console.assert(this instanceof EchoControls, 'this is not EchoControls', this);
    }
}