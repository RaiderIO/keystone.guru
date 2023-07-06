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

        /** @type {EchoUser[]} List of usernames currently connected */
        this._echoUsers = [];
        /** @type {EchoUser|null} The user that we're currently following, if any */
        this._echoUserFollow = null;
        /** @type {EchoUser|null} The user that we've most recently followed, even if we stopped following */
        this._echoUserRefollow = null;
        this._status = ECHO_STATUS_DISCONNECTED;

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
            new MousePositionHandler(this),
            new ViewPortHandler(this)
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
     * @param publicKey {String} The public key of the user.
     * @returns {EchoUser}
     */
    getUserByPublicKey(publicKey) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);
        let result = null;

        for (let i = 0; i < this._echoUsers.length; i++) {
            if (this._echoUsers[i].getPublicKey() === publicKey) {
                result = this._echoUsers[i];
                break;
            }
        }

        return result;
    }

    /**
     * Gets the color of a specific user.
     * @param publicKey {String}
     * @returns {string}
     */
    getUserColor(publicKey) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let user = this.getUserByPublicKey(publicKey);
        return user === null || user.getColor() === null ||
            typeof user.getColor() === 'undefined' || (user.getColor().length === 0 ? '#000' : user.getColor());
    }

    /**
     * Sets a user's color.
     * @param publicKey {String} The user's public key.
     * @param color {string} The new color of the user.
     */
    setUserColorByPublicKey(publicKey, color) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let echoUser = this.getUserByPublicKey(publicKey);
        echoUser.setColor(color);

        this.signal('user:colorchanged', {user: echoUser});
    }

    /**
     * Starts following the user around so that you're hands free (or turn it off).
     *
     * @param publicKey {String}
     */
    followUserByPublicKey(publicKey) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        // Unfollow the current user
        this.unfollowUser();

        this._echoUserFollow = this.getUserByPublicKey(publicKey);
        this._echoUserFollow.setFollowing(true);
        // Adjust the initial view port
        this._echoUserFollow.adjustViewportToThisUser();

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
     * Refollows the user we were previously following.
     */
    refollowUser() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        if (this._echoUserRefollow !== null) {
            this.followUserByPublicKey(this._echoUserRefollow.getPublicKey());
        }
    }

    /**
     * @returns {boolean} True if we're currently following a user, false if we're not.
     */
    isFollowingUser() {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        return this._echoUserFollow !== null;
    }

    /**
     * @returns {EchoUser|null} An EchoUser if we're following that person, or null if we're not following anyone at this moment.
     */
    getFollowingUser() {
        return this._echoUserFollow;
    }

    /**
     *
     * @param mousePositionReceivedEvent {Object}
     */
    onMousePositionReceived(mousePositionReceivedEvent) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        this.signal('mouseposition:received', mousePositionReceivedEvent.data.message);
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

        let existingEchoUser = this.getUserByPublicKey(rawUser.public_key);
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
            if (echoUserCandidate.getPublicKey() === rawUser.public_key) {
                this._echoUsers.splice(i, 1);
                echoUserCandidate.cleanup();
                this.signal('user:remove', {user: echoUserCandidate});
                // Remove all by the same user name
                i--;
            }
        }
    }
}
