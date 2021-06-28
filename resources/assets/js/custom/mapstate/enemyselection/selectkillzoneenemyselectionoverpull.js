class SelectKillZoneEnemySelectionOverpull extends EnemySelection {

    constructor(map, sourceMapObject, previousKillZoneEnemySelection = null) {
        super(map, sourceMapObject);

        this.currentSnackbarId = null;

        this.changedEnemyIds = [];
    }

    getName() {
        return 'SelectKillZoneEnemySelectionOverpull';
    }

    /**
     * Filters an enemy if it should be selectable or not.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);
        console.assert(source instanceof KillZone, 'source is not a KillZone', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return enemyCandidate.getKillZone() === null;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);
        return LeafletKillZoneIconEditMode;
    }

    start() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);

        super.start();

        let self = this;

        let template = Handlebars.templates['map_controls_snackbar_overpull_start_template'];

        this.currentSnackbarId = getState().addSnackbar(
            template($.extend({}, getHandlebarsDefaultVariables())), {
                onDomAdded: function () {
                    $('#overpull_selection_finished').on('click', function () {
                        // Trigger a stop
                        self.map.setMapState(null);
                    });
                }
            }
        );

        this.register('enemyselection:enemyselected', this, function (enemySelectedEvent) {
            let packEnemies = enemySelectedEvent.data.enemy.getPackBuddies();
            packEnemies.push(enemySelectedEvent.data.enemy);

            for (let i = 0; i < packEnemies.length; i++) {
                let enemy = packEnemies[i];

                // Toggle being overpulled or not
                enemy.setOverpulled(
                    !enemy.isOverpulled()
                );

                self.changedEnemyIds.push(enemy.id);
            }
        });
    }

    stop() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);

        super.stop();

        this._saveOverpulledEnemies();

        this.cleanup();
    }

    cleanup() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);

        super.cleanup();

        if (this.currentSnackbarId !== null) {
            getState().removeSnackbar(this.currentSnackbarId);
            this.currentSnackbarId = null;
        }
    }

    /**
     *
     */
    _saveOverpulledEnemies() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);

        let self = this;

        /** @type MapContextLiveSession */
        let mapContext = getState().getMapContext();

        let deletedIds = [];
        let addedIds = [];

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        for (let i = 0; i < this.changedEnemyIds.length; i++) {
            let changedEnemyId = this.changedEnemyIds[i];

            /** @type Enemy */
            let enemy = enemyMapObjectGroup.findMapObjectById(changedEnemyId);

            if (enemy.isOverpulled()) {
                addedIds.push(changedEnemyId);
            } else {
                deletedIds.push(changedEnemyId);
            }
        }

        // Prevent having these two requests running at the same time and causing race conditions and wrong results
        if (deletedIds.length > 0) {
            $.ajax({
                type: 'DELETE',
                url: `/ajax/${mapContext.getPublicKey()}/live/${mapContext.getLiveSessionPublicKey()}/overpulledenemy`,
                dataType: 'json',
                async: false,
                data: {
                    kill_zone_id: this.sourceMapObject.id,
                    enemy_ids: deletedIds,
                    no_result: addedIds.length > 0 ? 1 : 0
                },
                success: function (json) {
                    self._applyObsoleteEnemies.bind(self);

                    if (addedIds.length > 0) {
                        self._addNewOverpulledEnemies(addedIds);
                    }
                }
            });
        } else {
            this._addNewOverpulledEnemies(addedIds);
        }
    }

    /**
     *
     * @param enemyIds {Array}
     * @private
     */
    _addNewOverpulledEnemies(enemyIds) {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);

        /** @type MapContextLiveSession */
        let mapContext = getState().getMapContext();

        $.ajax({
            type: 'POST',
            url: `/ajax/${mapContext.getPublicKey()}/live/${mapContext.getLiveSessionPublicKey()}/overpulledenemy`,
            dataType: 'json',
            async: false,
            data: {
                kill_zone_id: this.sourceMapObject.id,
                enemy_ids: enemyIds
            },
            success: this._applyObsoleteEnemies.bind(this)
        });
    }

    /**
     *
     * @param json {object}
     * @private
     */
    _applyObsoleteEnemies(json) {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a EditKillZoneEnemySelection', this);

        // Override the enemy forces with a new value
        this.map.enemyForcesManager.setEnemyForcesOverride(json.enemy_forces);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
            let enemy = enemyMapObjectGroup.objects[i];

            // Set all enemies to be obsolete or not
            enemy.setObsolete(json.obsolete_enemy_ids.includes(enemy.id));
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
        let result = (new SelectKillZoneEnemySelectionOverpull(getState().getDungeonMap(), source))._filter(source, enemy);
        source.cleanup();
        return result;
    }
}