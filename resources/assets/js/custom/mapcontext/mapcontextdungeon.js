class MapContextDungeon extends MapContext {
    constructor(options) {
        super(options);

        let self = this;

        // Keep our list of NPCs up-to-date with whatever changes people may perform
        getState().register('echo:enabled', this, function () {
            window.Echo.join(getState().getMapContext().getEchoChannelName())
                .listen(`.npc-changed`, (e) => {
                    // Remove any existing NPC
                    self._removeRawNpcById(e.model.id);

                    // Add the new NPC
                    self._options.npcs.push(e.model);
                    console.log(`Added new npc`, e.model);
                }).listen(`.npc-deleted`, (e) => {
                // Only remove the NPC
                self._removeRawNpcById(e.model.id);
            });
        })
    }

    /**
     * Removes a raw NPC by its ID
     * @param id {Number}
     * @private
     */
    _removeRawNpcById(id) {
        console.assert(this instanceof MapContextDungeon, 'this is not a MapContextDungeon', this);

        for (let index in this._options.npcs) {
            if (this._options.npcs.hasOwnProperty(index)) {
                let rawNpc = this._options.npcs[index];
                if (rawNpc.id === id) {
                    // Remove it
                    let removed = this._options.npcs.splice(index, 1);
                    console.log(`Removed npc`, removed);
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