/**
 * Lets a live session viewer mark enemies outside of any pull as overpulled in the selected pull, or un-mark them,
 * one pack per click. The picks are saved when the selection stops; the state broadcast that follows brings every
 * viewer, this one included, in line with the server.
 */
class SelectKillZoneEnemySelectionOverpull extends EnemySelection {

    constructor(map, sourceMapObject, previousKillZoneEnemySelection = null) {
        super(map, sourceMapObject);

        this.currentSnackbarId = null;

        /** @type {Map<Number, Boolean>} Whether each enemy the user toggled should end up overpulled */
        this.isOverpulledByEnemyId = new Map();
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
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);
        console.assert(source instanceof KillZone, 'source is not a KillZone', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return enemyCandidate.getKillZone() === null;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);
        return LeafletKillZoneIconEditMode;
    }

    start() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

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
                self.toggleOverpulled(packEnemies[i]);
            }
        });
    }

    /**
     * @param enemy {LiveSessionEnemy}
     */
    toggleOverpulled(enemy) {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

        let isOverpulled = enemy.getOverpulledKillZoneId() === null;
        this.isOverpulledByEnemyId.set(enemy.id, isOverpulled);

        if (isOverpulled) {
            this.sourceMapObject.addOverpulledEnemy(enemy);
            enemy.setOverpulledKillZoneId(this.sourceMapObject.id);
        } else {
            enemy.setOverpulledKillZoneId(null);
        }
    }

    /**
     * @returns {boolean}
     */
    hasChanges() {
        return this.isOverpulledByEnemyId.size > 0;
    }

    stop() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

        super.stop();

        this._saveOverpulledEnemies();

        this.cleanup();
    }

    cleanup() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

        super.cleanup();

        if (this.currentSnackbarId !== null) {
            getState().removeSnackbar(this.currentSnackbarId);
            this.currentSnackbarId = null;
        }
    }

    /**
     * @private
     */
    _saveOverpulledEnemies() {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

        let self = this;

        /** @type MapContextLiveSession */
        let mapContext = getState().getMapContext();

        let deletedIds = [];
        let addedIds = [];

        for (let [enemyId, isOverpulled] of this.isOverpulledByEnemyId) {
            (isOverpulled ? addedIds : deletedIds).push(enemyId);
        }

        // Prevent having these two requests running at the same time and causing race conditions and wrong results
        if (deletedIds.length > 0) {
            $.ajax({
                type: 'DELETE',
                url: `/ajax/${mapContext.getPublicKey()}/live/${mapContext.getLiveSessionPublicKey()}/overpulledenemy`,
                dataType: 'json',
                data: {
                    kill_zone_id: this.sourceMapObject.id,
                    enemy_ids: deletedIds,
                    no_result: addedIds.length > 0 ? 1 : 0
                },
                success: function (json) {
                    // Obsolete enemies will be added when the overpulled enemies are pushed instead
                    if (addedIds.length > 0) {
                        self._addNewOverpulledEnemies(addedIds);
                    } else {
                        self._applyObsoleteEnemies(json);
                    }
                },
                error: this._revertToLiveSessionState.bind(this)
            });
        } else if (addedIds.length > 0) {
            this._addNewOverpulledEnemies(addedIds);
        }
    }

    /**
     *
     * @param enemyIds {Array}
     * @private
     */
    _addNewOverpulledEnemies(enemyIds) {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

        /** @type MapContextLiveSession */
        let mapContext = getState().getMapContext();

        $.ajax({
            type: 'POST',
            url: `/ajax/${mapContext.getPublicKey()}/live/${mapContext.getLiveSessionPublicKey()}/overpulledenemy`,
            dataType: 'json',
            data: {
                kill_zone_id: this.sourceMapObject.id,
                enemy_ids: enemyIds
            },
            success: this._applyObsoleteEnemies.bind(this),
            error: this._revertToLiveSessionState.bind(this)
        });
    }

    /**
     * The picks were not saved, so show the live session as the server last described it.
     * @private
     */
    _revertToLiveSessionState() {
        let liveSessionStateManager = this.map.liveSessionStateManager ?? null;
        if (liveSessionStateManager !== null) {
            liveSessionStateManager.applyCurrentState();
        }
    }

    /**
     * Shows the route correction right away; the state broadcast confirms it moments later, or carries it when
     * this response is lost.
     *
     * @param json {{obsolete_enemy_ids: Number[]}|undefined}
     * @private
     */
    _applyObsoleteEnemies(json) {
        console.assert(this instanceof SelectKillZoneEnemySelectionOverpull, 'this is not a SelectKillZoneEnemySelectionOverpull', this);

        if (!json || !Array.isArray(json.obsolete_enemy_ids)) {
            return;
        }

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getEnemyMapObjectGroup();
        for (let key in enemyMapObjectGroup.objects) {
            let enemy = enemyMapObjectGroup.objects[key];

            if (enemy instanceof LiveSessionEnemy) {
                enemy.setObsolete(json.obsolete_enemy_ids.includes(enemy.id));
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
        let result = (new SelectKillZoneEnemySelectionOverpull(getState().getDungeonMap(), source))._filter(source, enemy);
        source.cleanup();
        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        SelectKillZoneEnemySelectionOverpull,
    };
}
