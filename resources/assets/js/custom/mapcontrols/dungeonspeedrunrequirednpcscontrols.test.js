// DungeonSpeedrunRequiredNpcsControls is a global-script class extending MapControl (another
// global-script class). Stub that at module-load time and exercise _isOverflowNpc on a bare
// prototype instance (Object.create), same recipe as factiondisplaycontrols.test.js.

global.MapControl = class MapControl {
    constructor(map) {
        this.map = map;
    }
};

const DungeonSpeedrunRequiredNpcsControls = require('./dungeonspeedrunrequirednpcscontrols');

function createControls() {
    return Object.create(DungeonSpeedrunRequiredNpcsControls.prototype);
}

describe('DungeonSpeedrunRequiredNpcsControls._isOverflowNpc', () => {
    // #4427: on the facade floor, required npcs are never assigned that floor's id (they're only
    // ever assigned to real floors), so comparing floor_id against the current floor id would put
    // every required npc into the overflow container and leave the visible list empty.
    it('_isOverflowNpc_givenFacadeFloorAndNpcOnOtherFloor_returnsFalse', () => {
        const controls = createControls();

        const result = controls._isOverflowNpc({floor_id: 3}, 9, true);

        expect(result).toBe(false);
    });

    it('_isOverflowNpc_givenNonFacadeFloorAndNpcOnOtherFloor_returnsTrue', () => {
        const controls = createControls();

        const result = controls._isOverflowNpc({floor_id: 3}, 1, false);

        expect(result).toBe(true);
    });

    it('_isOverflowNpc_givenNonFacadeFloorAndNpcOnCurrentFloor_returnsFalse', () => {
        const controls = createControls();

        const result = controls._isOverflowNpc({floor_id: 1}, 1, false);

        expect(result).toBe(false);
    });
});
