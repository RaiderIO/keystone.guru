class MapContextDungeon extends MapContext {
    constructor(options) {
        super(options);
    }

    /**
     * When mapping a dungeon assume we have all affixes so things show up properly
     * @param affix {String}
     * @returns {boolean}
     */
    hasAffix(affix) {
        return true;
    }

    /**
     * Adds a new raw NPC to the map context
     * @param model {object}
     */
    addRawNpc(model) {
        console.assert(this instanceof MapContextDungeon, 'this is not a MapContextDungeon', this);

        this._options.npcs.push(model);

        this.signal('npc:added', {npc: model});
    }

    /**
     * Removes a raw NPC by its ID
     * @param id {Number}
     */
    removeRawNpcById(id) {
        console.assert(this instanceof MapContextDungeon, 'this is not a MapContextDungeon', this);

        for (let index in this._options.npcs) {
            if (this._options.npcs.hasOwnProperty(index)) {
                let rawNpc = this._options.npcs[index];
                if (rawNpc.id === id) {
                    // Remove it
                    let removed = this._options.npcs.splice(index, 1);

                    this.signal('npc:deleted', {npc: removed});
                    break;
                }
            }
        }
    }

    /**
     *
     * @returns {String}
     */
    getPublicKey() {
        return 'admin';
    }

    /**
     * @inheritDoc
     **/
    getTeeming() {
        return true;
    }

    /**
     * We are both seasonal indexes at once.
     * @returns {Number}
     */
    getSeasonalIndex() {
        return null;
    }

    /**
     *
     * @returns {Number}
     */
    getTeamId() {
        return -1;
    }

    /**
     *
     * @returns {[]}
     */
    getMdtEnemies() {
        return this._options.dungeon.enemiesMdt;
    }
}
