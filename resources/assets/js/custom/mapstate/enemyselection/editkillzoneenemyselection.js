class EditKillZoneEnemySelection extends EnemySelection {


    constructor(map, sourceMapObject, previousKillZoneEnemySelection = null) {
        super(map, sourceMapObject);

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('object:deleted', this, this._onMapObjectDeleted.bind(this));

        this._changedKillZoneIds = [];
        this._previousKillZoneEnemySelection = previousKillZoneEnemySelection;

        // Ignore these KillZone's events until they're removed from the blacklist
        this._blackListedKillZoneIds = [];

        // Prevent the previous killzone selection from triggering change events in THIS selection
        // This would cause a cascade of .save() calls since the previous change would cause this selection to
        // save it as 'this killzone changed', which is not what we want. This code prevents those calls from
        // affecting the new selection instance
        if (this._previousKillZoneEnemySelection instanceof EditKillZoneEnemySelection) {
            this._blackListedKillZoneIds = this._previousKillZoneEnemySelection._changedKillZoneIds;

            // Once the save:success is called, we can safely remove them from the blacklist
            for (let index in this._blackListedKillZoneIds) {
                if (this._blackListedKillZoneIds.hasOwnProperty(index)) {
                    let killZoneId = this._blackListedKillZoneIds[index];
                    let killZone = killZoneMapObjectGroup.findMapObjectById(killZoneId);
                    if (killZone !== null) {
                        killZone.register('save:success', this, this._killZoneSaveSuccess.bind(this));
                    }
                }
            }
        }
    }

    getName() {
        return 'EditKillZoneEnemySelection';
    }

    /**
     *
     * @param saveSuccessEvent
     * @private
     */
    _killZoneSaveSuccess(saveSuccessEvent) {
        let index = $.inArray(saveSuccessEvent.context.id, this._blackListedKillZoneIds);
        if (index !== -1) {
            // Remove it
            this._blackListedKillZoneIds.splice(index, 1);
        }

        // Unreg - we're done
        saveSuccessEvent.context.unregister('save:success', this);
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(this instanceof EditKillZoneEnemySelection, 'this is not a EditKillZoneEnemySelection', this);
        console.assert(source instanceof KillZone, 'source is not a KillZone', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return true;
        // If we're editing a pull that contains the last boss, disallow selecting of ANY pillar bosses
        if (source.isLinkedToLastBoss()) {
            return !enemyCandidate.isAwakenedNpc();
        } else {
            // Otherwise, just don't select pillar bosses that are at the last boss. They are managed by us only.
            return !enemyCandidate.isLinkedToLastBoss();
        }
        //enemyCandidate.getKillZone() === null || enemyCandidate.getKillZone().id === source.id;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof EditKillZoneEnemySelection, 'this is not a EditKillZoneEnemySelection', this);
        return LeafletKillZoneIconEditMode;
    }

    /**
     * Called when the source map object was deleted by the user, while currently selecting enemies.
     * @private
     */
    _onMapObjectDeleted(mapObjectDeletedEvent) {
        console.assert(this instanceof EditKillZoneEnemySelection, 'this is not a EditKillZoneEnemySelection', this);

        if (mapObjectDeletedEvent.data.object.id === this.sourceMapObject.id) {
            this.map.setMapState(null);
        }
    }


    start() {
        super.start();

        let self = this;

        // Register to all existing killzones so that we may find if there have been changes to them or not
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        for (let i = 0; i < killZoneMapObjectGroup.objects.length; i++) {
            let killZone = killZoneMapObjectGroup.objects[i];

            // Keep track of all killzones that were ever changed
            killZone.register(['killzone:enemyadded', 'killzone:enemyremoved'], this, function (killZoneChangedEvent) {
                let killZoneId = killZoneChangedEvent.context.id;
                if (!self._blackListedKillZoneIds.includes(killZoneId) && !self._changedKillZoneIds.includes(killZoneId)) {
                    self._changedKillZoneIds.push(killZoneId);
                }
            });
        }
    }

    stop() {
        super.stop();
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        for (let i = 0; i < killZoneMapObjectGroup.objects.length; i++) {
            let killZone = killZoneMapObjectGroup.objects[i];

            killZone.unregister(['killzone:enemyadded', 'killzone:enemyremoved'], this);
        }

        // Save all the killzones that were changed
        for (let i = 0; i < this._changedKillZoneIds.length; i++) {
            let killZone = killZoneMapObjectGroup.findMapObjectById(this._changedKillZoneIds[i]);
            // Only when the killzone was not deleted already and only if the killzone was already created
            if (killZone !== null && killZone.synced) {
                killZone.save();
            }
        }

        this.cleanup();
    }

    cleanup() {
        super.cleanup();

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('object:deleted', this);

        // If there's any black listed killzones left - unreg from save:success
        for (let index in this._blackListedKillZoneIds) {
            if (this._blackListedKillZoneIds.hasOwnProperty(index)) {
                let killZoneId = this._blackListedKillZoneIds[index];
                let killZone = killZoneMapObjectGroup.findMapObjectById(killZoneId);
                if (killZone !== null) {
                    killZone.unregister('save:success', this);
                }
            }
        }
    }

    /**
     *
     * @param enemy {Enemy}
     * @returns {boolean}
     */
    static isEnemySelectable(enemy) {
        // If it's looks stupid and it works it's not stupid
        let source = new KillZone(getState().getDungeonMap());
        let result = (new EditKillZoneEnemySelection(getState().getDungeonMap(), source))._filter(source, enemy);
        source.cleanup();
        return result;
    }
}