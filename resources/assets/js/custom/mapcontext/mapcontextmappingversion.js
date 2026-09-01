class MapContextMappingVersion extends MapContext {
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
        console.assert(this instanceof MapContextMappingVersionEdit, 'this is not a MapContextMappingVersionEdit', this);

        // Mirror the constructor's enrichment (see MapContext) since this npc wasn't part of the
        // initial dungeonNpcs/npcEnemyForces matching pass
        for (let i = 0; i < this._options.npcEnemyForces.length; i++) {
            if (this._options.npcEnemyForces[i].id === model.id) {
                model.enemy_forces = this._options.npcEnemyForces[i].enemy_forces;
                break;
            }
        }

        this._options.dungeonNpcs.push(model);

        this.signal('npc:added', {npc: model});
    }

    /**
     * Removes a raw NPC by its ID
     * @param id {Number}
     */
    removeRawNpcById(id) {
        console.assert(this instanceof MapContextMappingVersionEdit, 'this is not a MapContextMappingVersionEdit', this);

        for (let index in this._options.dungeonNpcs) {
            if (this._options.dungeonNpcs.hasOwnProperty(index)) {
                let rawNpc = this._options.dungeonNpcs[index];
                if (rawNpc.id === id) {
                    // Remove it
                    let removed = this._options.dungeonNpcs.splice(index, 1);

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
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapContextMappingVersion,
    };
}
