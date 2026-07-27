/**
 * Lets an admin click enemies to add them to (or remove them from) an enemy forces region.
 *
 * A region is deliberately allowed to span floors, and the admin mapping page never draws the facade,
 * so the only way to build a cross-floor region is to switch floors while this state is active.
 *
 * Two things make that work, and both are load-bearing:
 * - A floor change calls refreshLeafletMap(clearMapState = false), so the map state is kept.
 *   AdminDungeonMap must therefore FORWARD those arguments to its parent - it used to swallow them,
 *   which silently reinstated the default of true and killed the selection on every floor switch.
 * - Enemies are never cleaned up on a floor switch, only hidden (MapObjectGroup.clear() runs from
 *   reset(), which only the group's constructor calls), so their selectable flag and their
 *   `enemy:selected` registration survive.
 */
class EnemyForcesRegionEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'EnemyForcesRegionEnemySelection';
    }

    shouldRebuildEnemyVisuals() {
        return true;
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source {MapObject}
     * @param enemyCandidate {Enemy}
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);
        console.assert(source instanceof EnemyForcesRegion, 'source is not an EnemyForcesRegion', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return true;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);

        return LeafletKillZoneIconEditMode;
    }
}
