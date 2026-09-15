// ---------------------------------------------------------------------------
// Coverage for EnemyMapObjectGroup.setFocusedEnemy/getFocusedEnemy (#3946). This state used to live
// on the global StateManager, which was too broad a home for a purely enemy-layer concern (all of
// its callers and its one listener were already inside the enemy layer). Moved here per Wotuu's
// review comment on #3849.
//
// Follows the global-script recipe documented at the top of killzone.test.js, with one deliberate
// difference: Signalable is NOT stubbed here. Subscriber notification IS the subject of this file,
// so the tests register real listeners on the real event bus (signalable.js) and assert on what
// they receive - a stub would only prove the stub works.
// ---------------------------------------------------------------------------

const {Signalable} = require('../signalable');
global.MapObjectGroup = class MapObjectGroup extends Signalable {
};

const {EnemyMapObjectGroup} = require('./enemymapobjectgroup');

/**
 * An EnemyMapObjectGroup with just its Signalable bookkeeping initialized, bypassing the
 * constructor chain (which wants a real MapObjectGroupManager/map and getState()).
 *
 * @returns {EnemyMapObjectGroup}
 */
function makeGroup() {
    const group = Object.create(EnemyMapObjectGroup.prototype);
    group._signals = [];
    group._cleanedUp = false;
    group._focusedEnemy = null;

    return group;
}

/**
 * Records every payload delivered for `event` on the real Signalable bus.
 *
 * @param {EnemyMapObjectGroup} group
 * @param {String} event
 * @returns {Array<Object>}
 */
function listenFor(group, event) {
    const received = [];
    group.register(event, {}, (signal) => received.push(signal));

    return received;
}

describe('EnemyMapObjectGroup.setFocusedEnemy', () => {
    test('setFocusedEnemy_givenAnEnemy_notifiesSubscribers', () => {
        const group = makeGroup();
        const received = listenFor(group, 'focusedenemy:changed');
        const enemy = {id: 5};

        group.setFocusedEnemy(enemy);

        expect(group.getFocusedEnemy()).toBe(enemy);
        expect(received[0].data).toEqual({focusedenemy: enemy});
    });

    test('setFocusedEnemy_givenNull_notifiesWithNull', () => {
        const group = makeGroup();
        group.setFocusedEnemy({id: 5});
        const received = listenFor(group, 'focusedenemy:changed');

        group.setFocusedEnemy(null);

        expect(received[0].data).toEqual({focusedenemy: null});
    });
});
