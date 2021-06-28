const ECHO_STATUS_CONNECTED = 'connected';
const ECHO_STATUS_DISCONNECTED = 'connecting';

/**
 * @property {DungeonMap} map
 */
class Echo extends Signalable {
    constructor(map) {
        super();
        console.assert(map instanceof DungeonMap, 'map is not a DungeonMap', map);

        this.map = map;

        /** @type EchoUser[] List of usernames currently connected */
        this._echoUsers = [];
        /** @type EchoUser|null The user that we're currently following, if any */
        this._echoUserFollow = null;
        /** @type EchoUser|null The user that we've most recently followed, even if we stopped following */
        this._echoUserRefollow = null;
        this._status = ECHO_STATUS_DISCONNECTED;

        let mousePositionHandler = new MousePositionHandler(this);
        mousePositionHandler.register('message:received', this, this._onMousePositionReceived.bind(this));

        let viewPortHandler = new ViewPortHandler(this);
        viewPortHandler.register('message:received', this, this._onViewPortReceived.bind(this));

        this._handlers = [
            // NPC
            new NpcChangedHandler(this),
            new NpcDeletedHandler(this),

            new ColorChangedHandler(this),

            // Invite for a live session
            new InviteHandler(this),
            // Whenever someone has stopped the live session
            new StopHandler(this),

            // Overpulled enemies
            new OverpulledEnemyChangedHandler(this),
            new OverpulledEnemyDeletedHandler(this),

            // Whisper handlers
            mousePositionHandler,
            viewPortHandler
        ];
    }

    connect() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let self = this;

        window.startEcho();

        // This will probably not trigger the first time around, but it will trigger upon reconnect
        window.Echo.connector.socket.on('connect', function () {
            self._status = ECHO_STATUS_CONNECTED;
            self.signal('status:changed', {newStatus: self._status});
        });

        // Whenever disconnected..
        window.Echo.connector.socket.on('disconnect', function () {
            self._status = ECHO_STATUS_DISCONNECTED;
            self.signal('status:changed', {newStatus: self._status});

            // Reset the users that we have to prevent double users from showing up
            self._clearUsers();
        });

        // Keep track of the current users in this channel
        /** @type Channel */
        let presenceChannel = window.Echo.join(getState().getMapContext().getEchoChannelName())
            .here(rawUsers => {
                // Join any existing users already
                for (let index in rawUsers) {
                    if (rawUsers.hasOwnProperty(index)) {
                        self._addUser(rawUsers[index]);
                    }
                }
                self.signal('status:changed', {newStatus: ECHO_STATUS_CONNECTED});
            })
            .joining(rawUser => {
                self._addUser(rawUser);
            })
            .leaving(rawUser => {
                self._removeUser(rawUser);
            });

        // Attach all our handlers to the presence channel
        for (let index in this._handlers) {
            if (this._handlers.hasOwnProperty(index)) {
                let handler = this._handlers[index];

                handler.setup(presenceChannel);
            }
        }
    }

    /**
     * Get the current status.
     * @returns {string}
     */
    getStatus() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        return this._status;
    }

    /**
     * Gets the list of currently connected users.
     * @returns {object[]}
     */
    getUsers() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        return this._echoUsers;
    }

    /**
     * Gets a user by its name.
     * @param id {Number} The ID of the user.
     * @returns {EchoUser}
     */
    getUserById(id) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);
        let result = null;

        for (let i = 0; i < this._echoUsers.length; i++) {
            if (this._echoUsers[i].getId() === id) {
                result = this._echoUsers[i];
                break;
            }
        }

        return result;
    }

    /**
     * Gets the color of a specific user.
     * @param id {Number}
     * @returns {string}
     */
    getUserColor(id) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let user = this.getUserById(id);
        return user === null || user.getColor() === null ||
        typeof user.getColor() === 'undefined' || user.getColor().length === 0 ?
            '#000' : user.getColor();
    }

    /**
     * Sets a user's color.
     * @param id {Number} The user's id.
     * @param color {string} The new color of the user.
     */
    setUserColorById(id, color) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let echoUser = this.getUserById(id);
        echoUser.setColor(color);

        this.signal('user:colorchanged', {user: echoUser});
    }

    /**
     * Starts following the user around so that you're hands free (or turn it off)
     * @param id {Number}
     */
    followUserById(id) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        // Unfollow the current user
        this.unfollowUser();

        this._echoUserFollow = this.getUserById(id);
        this._echoUserFollow.setFollowing(true);
        // Adjust the initial view port
        this._adjustViewPortToUser(this._echoUserFollow);

        this._echoUserRefollow = this._echoUserFollow;

        this.map.leafletMap.on('mousedown', this.unfollowUser.bind(this));

        this.signal('user:follow', {user: this._echoUserFollow});
    }

    /**
     *
     */
    unfollowUser() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let previousEchoUserFollow = this._echoUserFollow;

        if (this._echoUserFollow !== null) {
            this._echoUserFollow.setFollowing(false);
            this._echoUserFollow = null;

            this.map.leafletMap.off('mousedown', this.unfollowUser);

            this.signal('user:unfollow', {user: previousEchoUserFollow});
        }
    }

    /**
     *
     */
    refollowUser() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        if (this._echoUserRefollow !== null) {
            this.followUserById(this._echoUserRefollow.getId());
        }
    }

    /**
     * Removes all users from the list.
     * @private
     */
    _clearUsers() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        // Let everyone know we removed all users
        for (let i = 0; i < this._echoUsers.length; i++) {
            this.signal('user:remove', {user: this._echoUsers[i]});
        }

        this._echoUsers = [];
    }

    /**
     * Adds a user to the internal list
     * @param rawUser {object}
     * @private
     */
    _addUser(rawUser) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let existingEchoUser = this.getUserById(rawUser.id);
        if (existingEchoUser === null) {
            let echoUser = new EchoUser(this.map, rawUser);
            this._echoUsers.push(echoUser);
            this.signal('user:add', {user: echoUser});
        }
    }


    /**
     * Removes a user from the internal list
     * @param rawUser {object}
     * @private
     */
    _removeUser(rawUser) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        for (let i = 0; i < this._echoUsers.length; i++) {
            let echoUserCandidate = this._echoUsers[i];
            if (echoUserCandidate.getId() === rawUser.id) {
                this._echoUsers.splice(i, 1);
                echoUserCandidate.cleanup();
                this.signal('user:remove', {user: echoUserCandidate});
                // Remove all by the same user name
                i--;
            }
        }
    }

    /**
     *
     * @param mousePositionReceivedEvent {Object}
     * @private
     */
    _onMousePositionReceived(mousePositionReceivedEvent) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        this.signal('mouseposition:received', mousePositionReceivedEvent.data.message);
    }

    /**
     *
     * @param viewPortReceivedEvent {Object}
     * @private
     */
    _onViewPortReceived(viewPortReceivedEvent) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        // Only if we're actively following someone
        if (this._echoUserFollow instanceof EchoUser) {
            this._adjustViewPortToUser(this._echoUserFollow);
        }
    }

    /**
     *
     * @param echoUser {EchoUser}
     * @private
     */
    _adjustViewPortToUser(echoUser) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);
        console.assert(echoUser instanceof EchoUser, 'echoUser is not an EchoUser', echoUser);

        // Only if their last center and zoom are known
        if (echoUser.getCenter() !== null && echoUser.getZoom() !== null) {
            let center = [echoUser.getCenter().lat, echoUser.getCenter().lng];

            // If we need to change floors, do so, otherwise change immediately
            if (echoUser.getFloorId() !== null && echoUser.getFloorId() !== getState().getCurrentFloor().id) {
                console.log(echoUser.getFloorId(), center, echoUser.getZoom());
                getState().setFloorId(echoUser.getFloorId(), center, echoUser.getZoom());
            } else {
                console.log(center, echoUser.getZoom());
                this.map.leafletMap.setView(center, echoUser.getZoom());
            }
        }
    }
}