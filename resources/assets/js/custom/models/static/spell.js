class Spell {
    constructor(remoteObject) {
        console.assert(remoteObject instanceof Object, 'Passed remoteObject is not an Object!', remoteObject);

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
    }
}
