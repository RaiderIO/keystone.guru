class Spell {
    constructor(remoteObject) {
        console.assert(remoteObject instanceof Object, 'Passed remoteObject is not an Object!', remoteObject);

        /** @type Number */
        this.id = remoteObject.id;
        /** @type string */
        this.dispel_type = remoteObject.dispel_type;
        /** @type string */
        this.icon_name = remoteObject.icon_name;
        /** @type string */
        this.name = remoteObject.name;
        /** @type int */
        this.schools_mask = remoteObject.schools_mask;
        /** @type int */
        this.aura = remoteObject.aura;
        /** @type string */
        this.icon_url = remoteObject.icon_url;
        /** @type string */
        this.wowhead_url = remoteObject.wowhead_url;
    }
}
