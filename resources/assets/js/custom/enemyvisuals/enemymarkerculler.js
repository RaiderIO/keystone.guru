/**
 * Class put on an enemy's marker element to take it out of layout and paint while it is far enough
 * outside the viewport for nobody to see it. Hidden rather than removed from the map: Leaflet's
 * Marker#_removeIcon() nulls its icon element, so re-adding the layer rebuilds that DOM from the
 * divIcon's html and drops every handler and cached selector bound into it.
 */
const ENEMY_CULL_CLASS = 'map_enemy_visual_culled';
/**
 * How much of the viewport is kept populated beyond each edge, as a fraction of its size. The
 * visibility pass is throttled to 50ms and a single wheel step grows the bounds by roughly 40%,
 * so an enemy must be kept rendered well past the edge to not visibly pop in.
 */
const ENEMY_CULL_BOUNDS_PADDING = 0.4;
/**
 * Below this many enemies on a floor the layout being avoided is not measurable, so the map is
 * left to behave exactly as it did before. Regular dungeon floors sit well under it; raids and
 * facade floors (which draw every floor's enemies at once) are what carry the cost.
 */
const ENEMY_CULL_MIN_ENEMIES = 150;

/**
 * Takes the DOM of enemies that are far outside the viewport out of layout and paint, and puts it
 * back as they approach it again. On a facade floor the large majority of enemies are off screen
 * at any time, and the browser was laying out and painting all of them on every zoom.
 */
class EnemyMarkerCuller {

    /**
     * @param map {DungeonMap}
     * @param allEnemies {Enemy[]} Live reference to the enemies to consider for culling - the
     * caller is expected to push/splice into this same array as enemies are added/removed.
     * @param getHoveredEnemy {function(): Enemy|null} Returns the enemy currently under the mouse,
     * which - like an enemy with an open radial or a highlighted pack member - must not disappear
     * out from under the user just because the map moved.
     */
    constructor(map, allEnemies, getHoveredEnemy) {
        this.map = map;
        this._allEnemies = allEnemies;
        this._getHoveredEnemy = getHoveredEnemy;

        // Whether any marker currently carries the cull class, so dropping back below
        // ENEMY_CULL_MIN_ENEMIES can put back what was hidden while above it
        this._cullingApplied = false;
        this._scheduled = false;
    }

    /**
     * Queues a cull pass for the next frame, coalescing however many callers ask for one in the
     * meantime into a single traversal.
     */
    scheduleUpdate() {
        if (this._scheduled) {
            return;
        }

        this._scheduled = true;
        window.requestAnimationFrame(() => {
            this._scheduled = false;
            this.update(this.map.leafletMap.getBounds());
        });
    }

    /**
     * @param bounds {L.LatLngBounds} The current map bounds, unpadded.
     */
    update(bounds) {
        console.assert(this instanceof EnemyMarkerCuller, 'this is not an EnemyMarkerCuller!', this);

        if (this._allEnemies.length < ENEMY_CULL_MIN_ENEMIES) {
            // Deleting enemies in the map editor can take a floor back under the threshold, and
            // whatever was culled on the way down would otherwise stay hidden for good.
            if (this._cullingApplied) {
                this._setCullClasses(() => false);
                this._cullingApplied = false;
            }
            return;
        }

        let paddedBounds = bounds.pad(ENEMY_CULL_BOUNDS_PADDING);
        this._cullingApplied = true;

        this._setCullClasses(enemy => !this._shouldKeepEnemyRendered(enemy, paddedBounds));
    }

    /**
     * @param shouldCull {function(Enemy): boolean}
     * @private
     */
    _setCullClasses(shouldCull) {
        for (let i = 0; i < this._allEnemies.length; i++) {
            let enemy = this._allEnemies[i];

            // Leaflet's own handle, rather than the visual's cached jQuery selectors: those are
            // only populated by buildVisual(), which never runs for an enemy that has been off
            // screen since it was added - exactly the ones that need culling.
            let icon = enemy.layer === null ? null : enemy.layer._icon;
            if (!icon) {
                continue;
            }

            icon.classList.toggle(ENEMY_CULL_CLASS, shouldCull(enemy));
        }
    }

    /**
     * @param enemy {Enemy}
     * @param paddedBounds {L.LatLngBounds}
     * @returns {boolean} True if this enemy's DOM must stay in layout, false if it can be hidden.
     * @private
     */
    _shouldKeepEnemyRendered(enemy, paddedBounds) {
        if (paddedBounds.contains(enemy.layer.getLatLng())) {
            return true;
        }

        if (enemy.visual === null) {
            return true;
        }

        // Mid-interaction: an open radial, a highlighted pack member or the enemy under the cursor
        // must not disappear out from under the user just because the map moved.
        return enemy === this._getHoveredEnemy() ||
            enemy.visual._circleMenu !== null ||
            enemy.visual.isHighlighted();
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyMarkerCuller,
        ENEMY_CULL_CLASS,
        ENEMY_CULL_BOUNDS_PADDING,
        ENEMY_CULL_MIN_ENEMIES,
    };
}
