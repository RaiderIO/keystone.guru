const ECHO_STATUS_CONNECTED = 'connected';
const ECHO_STATUS_DISCONNECTED = 'connecting';

class Echo extends Signalable {
    constructor(map) {
        super();
        this.map = map;

        /** List of usernames currently connected */
        this._users = [];
        this._status = ECHO_STATUS_DISCONNECTED;
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
        window.Echo.join(getState().getEchoChannelName())
            .here(users => {
                // Join any existing users already
                for (let index in users) {
                    if (users.hasOwnProperty(index)) {
                        self._addUser(users[index]);
                    }
                }
                self.signal('status:changed', {newStatus: ECHO_STATUS_CONNECTED});
            })
            .joining(user => {
                self._addUser(user);
            })
            .leaving(user => {
                self._removeUser(user);
            })
            .listen('.user-color-changed', (e) => {
                self._setUserColorById(e.user.id, e.color);
            });
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

        return this._users;
    }

    /**
     * Gets a user by its name.
     * @param id int The ID of the user.
     * @returns {null|object}
     */
    getUserById(id) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);
        let result = null;

        for (let i = 0; i < this._users.length; i++) {
            if (this._users[i].id === id) {
                result = this._users[i];
                break;
            }
        }

        return result;
    }

    /**
     * Gets the color of a specific user.
     * @param name
     * @returns {string}
     */
    getUserColor(name) {
        let user = this.getUserByName(name);
        return user === null || user.color.length === 0 ? '#000' : user.color;
    }

    /**
     * Removes all users from the list.
     * @private
     */
    _clearUsers() {
        // Let everyone know we removed all users
        for (let i = 0; i < this._users.length; i++) {
            this.signal('user:remove', {user: this._users[i]});
        }

        this._users = [];
    }

    /**
     * Adds a user to the internal list
     * @param user {object}
     * @private
     */
    _addUser(user) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        let existingUser = this.getUserById(user.id);
        if (existingUser === null) {
            // May be unset when not our own user, but this confuses handlebars
            user.self = user.id === getState().getUser().id;

            this._users.push(user);
            this.signal('user:add', {user: user});
        }
    }


    /**
     * Removes a user from the internal list
     * @param user {object}
     * @private
     */
    _removeUser(user) {
        console.assert(this instanceof Echo, 'this is not an Echo', this);

        for (let i = 0; i < this._users.length; i++) {
            let userCandidate = this._users[i];
            if (userCandidate.id === user.id) {
                this._users.splice(i, 1);
                this.signal('user:remove', {user: user});
                // Remove all by the same user name
                i--;
            }
        }
    }

    /**
     * Sets a user's color.
     * @param id int The user's id.
     * @param color string The new color of the user.
     * @private
     */
    _setUserColorById(id, color) {
        // Update by reference - do not use getUserById()
        let user = null;

        for (let i = 0; i < this._users.length; i++) {
            if (this._users[i].id === id) {
                user = this._users[i];
                this._users[i].color = color;
                break;
            }
        }

        this.signal('user:colorchanged', {user: user});
    }
}