/**
 * Draws polylines from an admin map object's marker to each of its member enemies, so a mapper can
 * see at a glance which enemies belong to it. Owns the layer group lifecycle (create/attach/remove);
 * the owning map object decides when to (re)draw, where to attach, and which latlngs to connect.
 *
 * Shared by AdminEnemyPatrol and AdminEnemyForcesCheckpoint.
 */
class AdminEnemyConnections {
    /**
     * @param polylineOptions {Object} Passed to every L.polyline (see c.map.adminenemypatrol/
     *     c.map.adminenemyforcescheckpoint in constants.js).
     */
    constructor(polylineOptions) {
        this.polylineOptions = polylineOptions;

        // Where our layer group is currently attached, so remove() detaches from the right parent.
        this.parentLayerGroup = null;
        this.layerGroup = null;
    }

    /**
     * (Re)draws the connection lines. A parent is passed per draw rather than fixed at construction:
     * patrols attach to their map object group's layer group (so the Map Elements toggle covers them
     * for free), checkpoints to the leaflet map itself (they gate on the group's visibility manually).
     *
     * @param parentLayerGroup {L.LayerGroup|L.Map} What to attach the lines to.
     * @param centerLatLng {L.LatLng} Where every line starts.
     * @param enemyLatLngs {L.LatLng[]} Where each line ends.
     */
    draw(parentLayerGroup, centerLatLng, enemyLatLngs) {
        console.assert(this instanceof AdminEnemyConnections, 'this is not an AdminEnemyConnections', this);

        this.remove();

        if (enemyLatLngs.length === 0) {
            return;
        }

        this.layerGroup = new L.LayerGroup();

        for (let index in enemyLatLngs) {
            this.layerGroup.addLayer(
                L.polyline([
                    [centerLatLng.lat, centerLatLng.lng],
                    enemyLatLngs[index],
                ], this.polylineOptions)
            );
        }

        // Do not prevent clicking on anything else
        this.layerGroup.setZIndex(-1000);

        this.parentLayerGroup = parentLayerGroup;
        this.parentLayerGroup.addLayer(this.layerGroup);
    }

    /**
     * Removes any currently drawn connection lines.
     */
    remove() {
        console.assert(this instanceof AdminEnemyConnections, 'this is not an AdminEnemyConnections', this);

        if (this.layerGroup !== null) {
            this.parentLayerGroup.removeLayer(this.layerGroup);
            this.parentLayerGroup = null;
            this.layerGroup = null;
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AdminEnemyConnections,
    };
}
