class EchoUser extends Signalable {
    constructor(user) {
        super();

        // May be unset when not our own user, but this confuses handlebars
        this.self = user.id === getState().getUser().id;

        this.id = user.id;
        this.name = user.name;
        this.initials = user.initials;
        this.color = user.color;
        this.avatar_url = user.avatar_url; // May be null if not set
        this.anonymous = user.anonymous;
        this.url = user.url;
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
}