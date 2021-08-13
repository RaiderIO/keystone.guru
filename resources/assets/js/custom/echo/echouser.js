class EchoUser extends Signalable {

    /**
     *
     * @param map {DungeonMap}
     * @param user {Object}
     */
    constructor(map, user) {
        super();

        this.map = map;

        // May be unset when not our own user, but this confuses handlebars
        this.self = user.public_key === getState().getUser().public_key;

        this.public_key = user.public_key;
        this.name = user.name;
        this.initials = user.initials;
        this.color = user.color;
        this.avatar_url = user.avatar_url; // May be null if not set
        this.anonymous = user.anonymous;
        this.url = user.url;

        // May be set if we're following this user
        this.following = false;

        // May be received from this user when available
        this.center = null;
        this.zoom = null;
        this.floor_id = null;

        // Create a map object for this echo user
        /** @type UserMousePositionMapObjectGroup */
        let userMousePositionMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_USER_MOUSE_POSITION);
        this.mapobject = userMousePositionMapObjectGroup.createNewUserMousePosition(this);
    }

    /**
     * @returns {Number}
     */
    getId() {
        return this.id;
    }

    /**
     * @returns {string}
     */
    getName() {
        return this.name;
    }

    /**
     * @returns {string}
     */
    getInitials() {
        return this.initials;
    }

    /**
     * @returns {string}
     */
    getColor() {
        return this.color;
    }

    /**
     *
     * @param color {string}
     */
    setColor(color) {
        this.color = color;
    }

    /**
     * @returns {string}
     */
    getAvatarUrl() {
        return this.avatar_url;
    }

    /**
     * @returns {boolean}
     */
    getAnonymous() {
        return this.anonymous;
    }

    /**
     * @returns {string}
     */
    getUrl() {
        return this.url;
    }

    /**
     *
     * @returns {Number|null}
     */
    getCenter() {
        return this.center;
    }

    /**
     *
     * @param center
     */
    setCenter(center) {
        this.center = center;
    }

    /**
     *
     * @returns {Number|null}
     */
    getZoom() {
        return this.zoom;
    }

    /**
     *
     * @param zoom {Number}
     */
    setZoom(zoom) {
        this.zoom = zoom;
    }

    /**
     *
     * @returns {Number|null}
     */
    getFloorId() {
        return this.floor_id;
    }

    /**
     *
     * @param floorId {Number}
     */
    setFloorId(floorId) {
        this.floor_id = floorId;
    }

    /**
     *
     * @param following {Boolean}
     */
    setFollowing(following) {
        this.following = following;
    }

    /**
     * @returns {Boolean}
     */
    getFollowing() {
        return this.following;
    }



    cleanup() {
        super.cleanup();

        this.mapobject.localDelete();
        this.mapobject.cleanup();
    }
}