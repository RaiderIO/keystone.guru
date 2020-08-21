class MapIconType {
    constructor(remoteObject) {
        console.assert(remoteObject instanceof Object, 'Passed map is not an Object!', map);

        this.map = map;
        this.id = remoteObject.id;
        this.key = remoteObject.key;
        this.name = remoteObject.name;
        this.width = remoteObject.width;
        this.height = remoteObject.height;
        this.admin_only = remoteObject.admin_only === 1;
    }

    /**
     * Checks if this map icon type is an awakened obelisk or not.
     * @returns {boolean}
     */
    isAwakenedObelisk() {
        return this.id >= 17 && this.id <= 20;
    }

    isEditable() {
        return (getState().isMapAdmin()) || !this.admin_only;
    }

    isDeletable() {
        return this.isEditable();
    }
}