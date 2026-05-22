class Spell {
    constructor(remoteObject) {
        console.assert(remoteObject instanceof Object, 'Passed remoteObject is not an Object!', remoteObject);

        /** @type Number */
        this.id = remoteObject.id;
        /** @type string */
        this.category = remoteObject.category;
        /** @type string */1
        this.cooldown_group = remoteObject.cooldown_group;
        /** @type string */
        this.dispel_type = remoteObject.dispel_type;
        /** @type string */
        this.mechanic = remoteObject.mechanic;
        /** @type string */
        this.icon_name = remoteObject.icon_name;
        /** @type string */
        this.name = remoteObject.name;
        /** @type int */
        this.schools_mask = remoteObject.schools_mask;
        /** @type int */
        this.miss_types_mask = remoteObject.miss_types_mask;
        /** @type int */
        this.aura = remoteObject.aura;
        /** @type int */
        this.cast_time = remoteObject.cast_time;
        /** @type int */
        this.duration = remoteObject.duration;
        /** @type string */
        this.icon_url = remoteObject.icon_url;
        /** @type string */
        this.wowhead_url = remoteObject.wowhead_url;
    }

    /**
     * @param mapping {Object}
     * @param mask {Number}
     * @param translationPrefix {string|null}
     * @returns {string[]}
     * @private
     */
    _maskToReadableArray(mapping, mask, translationPrefix = null) {
        let result = [];

        if (typeof mapping === 'undefined' || mapping === null) {
            return result;
        }

        for (let key in mapping) {
            let value = mapping[key];
            let bitmask, name;

            // bitmask => name
            if (!isNaN(key)) {
                bitmask = parseInt(key);
                name = value;
            }
            // name => bitmask
            else {
                bitmask = value;
                name = key;
            }

            if ((mask & bitmask) !== 0) {
                if (translationPrefix !== null) {
                    result.push(lang.get(`${translationPrefix}.${name}`));
                } else {
                    result.push(name);
                }
            }
        }

        return result;
    }

    /**
     * @returns {string[]}
     */
    getSchools() {
        return this._maskToReadableArray(
            getState().getMapContext().getStaticSpellSchools(),
            this.schools_mask,
            'spellschools'
        );
    }

    /**
     * @returns {string[]}
     */
    getMissTypes() {
        return this._maskToReadableArray(
            getState().getMapContext().getStaticSpellMissTypes(),
            this.miss_types_mask,
            'spellmisstypes'
        );
    }

    /**
     * @returns {string}
     */
    getDispelType() {
        return lang.get(this.dispel_type);
    }

    /**
     * @returns {string}
     */
    getMechanic() {
        return lang.get(this.mechanic);
    }
}
