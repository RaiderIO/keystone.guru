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

    isEditable() {
        return (getState().getDungeonRoute().publicKey === '') || !this.admin_only;
    }
}