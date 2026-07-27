class EnemySelection extends MapObjectMapState {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'EnemySelection';
    }

    // NOTE: disablesTooltips() is deliberately NOT overridden here. Suppressing enemy tooltips is only
    // wanted where the tooltip fights the selection visuals (the admin mapping page's couple/patrol/
    // checkpoint flows), which override it individually. Pull-building in the route editor also runs
    // through an EnemySelection, and there the tooltip carries the npc name, health and enemy forces a
    // router needs to decide whether to click an enemy at all - so it must keep working.

    /**
     * Filter function which should be overriden in implementing classes.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        return true;
    }

    /**
     * Get the new icon that describes the way the enemy looks when being able to be selected.
     * @returns {null}
     * @protected
     */
    _getLayerIcon() {
        return null;
    }

    /**
     * Starts select mode on this Selection, if no other select mode was enabled already.
     */
    start() {
        super.start();
        console.assert(this instanceof EnemySelection, 'this is not an EnemySelectionMapState', this);

        let self = this;

        // https://stackoverflow.com/a/18008067/771270
        // this._oldMapObjectIcon = this.sourceMapObject.layer.options.icon;
        // this.sourceMapObject.layer.setIcon(this._getLayerIcon());

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        for (let key in enemyMapObjectGroup.objects) {
            let enemy = enemyMapObjectGroup.objects[key];
            // Check if we should set this enemy to be selectable or not
            enemy.setSelectable(this._filter(this.sourceMapObject, enemy));

            enemy.register('enemy:selected', this, function (enemySelectedEvent) {
                let enemy = enemySelectedEvent.context;
                console.assert(enemy instanceof Enemy, 'enemy is not an Enemy', enemy);
                console.assert(self instanceof EnemySelection, 'this is not an EnemySelectionMapState', self);

                self.signal('enemyselection:enemyselected', {
                    sourceMapObject: self.sourceMapObject,
                    enemy: enemy,
                    ignorePackBuddies: enemySelectedEvent.data.clickEvent.originalEvent.ctrlKey
                });
            });
        }

        // Cannot start editing things while we're doing this.
        // @TODO https://stackoverflow.com/questions/40414970/disable-leaflet-draw-delete-button
        $('.leaflet-draw-edit-edit').addClass('leaflet-disabled').refreshTooltips();
        $('.leaflet-draw-edit-remove').addClass('leaflet-disabled').refreshTooltips();
    }

    /**
     * Stops selecting enemies.
     */
    stop() {
        super.stop();
        console.assert(this instanceof EnemySelection, 'this is not an EnemySelectionMapState', this);
        let self = this;

        // Restore the previous icon
        // this.sourceMapObject.layer.setIcon(this._oldMapObjectIcon);

        // Revert all things we did to enemies
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            // Enemies no longer present themselves as selectable
            if (enemy.isSelectable()) {
                enemy.setSelectable(false);
            }
            enemy.unregister('enemy:selected', self);
        });

        // Ok we're clear, may edit again (there's always something to edit because this EnemySelectionMapState was triggered by one)
        if (this.map.editableLayers.getLayers().length > 0) {
            $('.leaflet-draw-edit-edit').removeClass('leaflet-disabled').refreshTooltips();
            $('.leaflet-draw-edit-remove').removeClass('leaflet-disabled').refreshTooltips();
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemySelection,
    };
}
