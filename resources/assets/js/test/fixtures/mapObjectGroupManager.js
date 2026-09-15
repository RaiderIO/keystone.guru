// ---------------------------------------------------------------------------
// A stand-in MapObjectGroupManager for tests whose code under test reads map object groups off
// `map.mapObjectGroupManager`.
//
// The fake is built on the real MapObjectGroupManager prototype, so the named accessors
// (getEnemyMapObjectGroup(), getKillZoneMapObjectGroup(), ...) are the production ones; only
// getByName() - the single lookup they all go through - is replaced by the test's resolver. That keeps
// each test in charge of which groups exist without having to stub every accessor by hand.
//
// The accessors read the MAP_OBJECT_GROUP_* constants as bare globals, so a test must install the
// ones its code under test asks for, exactly as it had to before.
// ---------------------------------------------------------------------------

/**
 * @param {function(string): (Object|null|undefined)} resolveGroup Returns the group registered under a
 *        name; null or undefined means "this map has no such group".
 * @returns {MapObjectGroupManager}
 */
function fakeMapObjectGroupManager(resolveGroup) {
    // Required lazily so a test file's own globals (Signalable in particular) are in place first
    globalThis.Signalable ??= class Signalable {
    };
    const {MapObjectGroupManager} = require('../../custom/mapobjectgroups/mapobjectgroupmanager');

    const manager = Object.create(MapObjectGroupManager.prototype);
    manager.getByName = (name) => resolveGroup(name) ?? null;

    return manager;
}

module.exports = {fakeMapObjectGroupManager};
