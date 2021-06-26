class SelectKillZoneEnemySelectionOverpull extends EnemySelection {

    constructor(map, sourceMapObject, previousKillZoneEnemySelection = null) {
        super(map, sourceMapObject);


        this.currentSnackbarId = null;
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
            }
        });
    }

    stop() {
        super.stop();

        this.cleanup();
    }

    cleanup() {
        super.cleanup();

        if (this.currentSnackbarId !== null) {
            getState().removeSnackbar(this.currentSnackbarId);
            this.currentSnackbarId = null;
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