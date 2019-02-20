class EnemySelection extends Signalable {
    constructor(map, sourceMapObject) {
        super();
        console.assert(map instanceof DungeonMap, map, 'map is not a Map');
        console.assert(sourceMapObject instanceof MapObject, sourceMapObject, 'sourceMapObject is not a MapObject');

        this.map = map;
        this.sourceMapObject = sourceMapObject;

        this._oldMapObjectIcon = null;
    }

    /**
     * Filter function which should be overriden in implementing classes.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate){
        return true;
    }

    /**
     * Get the new icon that describes the way the enemy looks when being able to be selected.
     * @returns {null}
     * @protected
     */
    _getLayerIcon(){
        return null;
    }

    /**
     * Get the map object that initiated this selection.
     * @returns {*}
     */
    getMapObject(){
        return this.sourceMapObject;
    }

    /**
     * Starts select mode on this Selection, if no other select mode was enabled already.
     */
    startSelectMode() {
        console.assert(this instanceof EnemySelection, this, 'this is not an EnemySelection');
        let self = this;

        // https://stackoverflow.com/a/18008067/771270
        this._oldMapObjectIcon = this.sourceMapObject.layer.options.icon;
        this.sourceMapObject.layer.setIcon(this._getLayerIcon());

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            // Check if we should set this enemy to be selectable or not
            if (self._filter(self.sourceMapObject, enemy)) {
                enemy.setSelectable(!enemy.isSelectable());
            }

            enemy.register('enemy:selected', self, function (data) {
                let enemy = data.context;
                console.assert(enemy instanceof Enemy, enemy, 'enemy is not an Enemy');
                console.assert(self instanceof EnemySelection, self, 'this is not an EnemySelection');

                self.signal('enemyselection:enemyselected', {enemy: enemy});
            });
        });

        // Cannot start editing things while we're doing this.
        // @TODO https://stackoverflow.com/questions/40414970/disable-leaflet-draw-delete-button
        $('.leaflet-draw-edit-edit').addClass('leaflet-disabled');
        $('.leaflet-draw-edit-remove').addClass('leaflet-disabled');
    }

    /**
     * Stops selecting enemies.
     */
    cancelSelectMode() {
        console.assert(this instanceof EnemySelection, this, 'this is not an EnemySelection');
        let self = this;

        // Restore the previous icon
        this.sourceMapObject.layer.setIcon(this._oldMapObjectIcon);

        // Revert all things we did to enemies
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            // Enemies no longer present themselves as selectable
            enemy.setSelectable(false);
            enemy.unregister('enemy:selected', self);
        });

        // Ok we're clear, may edit again (there's always something to edit because this EnemySelection was triggered by one)
        $('.leaflet-draw-edit-edit').removeClass('leaflet-disabled');
        $('.leaflet-draw-edit-remove').removeClass('leaflet-disabled');
    }
}