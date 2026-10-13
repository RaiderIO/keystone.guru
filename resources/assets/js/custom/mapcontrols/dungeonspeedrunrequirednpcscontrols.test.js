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

describe('DungeonSpeedrunRequiredNpcsControls.refreshUI', () => {
    let previousJQuery;
    let previousGetState;
    let previousHandlebars;

    beforeEach(() => {
        previousJQuery = global.$;
        previousGetState = global.getState;
        previousHandlebars = global.Handlebars;
        global.$ = require('jquery');
        // Echoes the row data, so a test can read back what was rendered into which container
        global.Handlebars = {
            templates: {
                map_dungeon_speedrun_required_npcs_row_template: (data) =>
                    `<div class="required_npc_row" data-id="${data.id}">${data.npcNames.map((npcName) => npcName.name).join(',')} ${data.killed_count}/${data.count}</div>`,
            },
        };
        document.body.innerHTML = `
            <div id="map_dungeon_speedrun_required_npcs"><div class="stale_row"></div></div>
            <div id="edit_route_dungeon_speedrun_required_npcs_container_overflow"><div class="stale_row"></div></div>
            <div id="edit_route_dungeon_speedrun_required_npcs_toggle_container"></div>`;
    });

    afterEach(() => {
        global.$ = previousJQuery;
        global.getState = previousGetState;
        global.Handlebars = previousHandlebars;
        document.body.innerHTML = '';
    });

    /**
     * A controls instance on a dungeon with one required npc on floor 1 and one on floor 2. Npc 100 is on
     * floor 1 and killed twice (enemies 1 and 2), npc 200 is on floor 2 and not killed.
     *
     * @param currentFloor {Object}
     * @returns {DungeonSpeedrunRequiredNpcsControls}
     */
    function createControlsOnFloor(currentFloor) {
        const enemies = {1: {npc: {id: 100}}, 2: {npc: {id: 100}}, 3: {npc: {id: 300}}};
        const npcs = {100: {name: 'Boss'}, 200: {name: 'Add'}};
        const requiredNpcs = [
            {id: 11, floor_id: 1, count: 2, dungeon_speedrun_required_npc_npcs: [{npc_id: 100}]},
            {id: 12, floor_id: 2, count: 1, dungeon_speedrun_required_npc_npcs: [{npc_id: 200}]},
        ];

        global.getState = () => ({
            getCurrentFloor: () => currentFloor,
            getMapContext: () => ({
                getDungeonDifficulty: () => 1,
                getDungeonSpeedrunRequiredNpcs: (difficulty) => difficulty === 1 ? requiredNpcs : [],
                findNpcById: (npcId) => npcs[npcId],
            }),
        });

        const controls = createControls();
        controls.map = {
            mapObjectGroupManager: {
                getKillZoneMapObjectGroup: () => ({objects: [{enemies: [1, 3]}, {enemies: [2]}]}),
                getEnemyMapObjectGroup: () => ({findMapObjectById: (enemyId) => enemies[enemyId]}),
            },
        };

        return controls;
    }

    /**
     * @param selector {String}
     * @returns {String[]}
     */
    function renderedRows(selector) {
        return $(selector).children().toArray().map((row) => row.textContent);
    }

    it('refreshUI_givenRealFloor_putsOtherFloorNpcsInTheOverflowWithTheirKilledCount', () => {
        // Arrange
        const controls = createControlsOnFloor({id: 1, facade: 0});

        // Act
        controls.refreshUI();

        // Assert
        expect(renderedRows('#map_dungeon_speedrun_required_npcs')).toEqual(['Boss 2/2']);
        expect(renderedRows('#edit_route_dungeon_speedrun_required_npcs_container_overflow')).toEqual(['Add 0/1']);
        expect($('#edit_route_dungeon_speedrun_required_npcs_toggle_container').css('display')).not.toBe('none');
    });

    it('refreshUI_givenFacadeFloor_putsEveryNpcInTheMainListAndHidesTheOverflowToggle', () => {
        // Arrange
        const controls = createControlsOnFloor({id: 9, facade: 1});

        // Act
        controls.refreshUI();

        // Assert
        expect(renderedRows('#map_dungeon_speedrun_required_npcs')).toEqual(['Boss 2/2', 'Add 0/1']);
        expect(renderedRows('#edit_route_dungeon_speedrun_required_npcs_container_overflow')).toEqual([]);
        expect($('#edit_route_dungeon_speedrun_required_npcs_toggle_container').css('display')).toBe('none');
    });
});
