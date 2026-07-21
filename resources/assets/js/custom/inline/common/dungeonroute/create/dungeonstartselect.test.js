// ---------------------------------------------------------------------------
// CommonDungeonrouteCreateDungeonstartselect is a global-script style class: it extends the bare
// global `InlineCode` and uses `$`/`jQuery` and `refreshSelectPickers` (stubbed here with a spy - the
// real Tom Select integration is exercised by the full-form integration suite at
// custom/inline/common/forms/createroute.serialization.test.js) at runtime.
//
// DOM is a minimal subset of the Create Route form (just the dungeon + dungeon-start selects),
// reusing the sentinel dungeon ids and DUNGEON_STARTS_BY_DUNGEON_ID shape from
// test/fixtures/createRouteForm.js: RETAIL_DUNGEON_ALPHA_ID has exactly one start (stays hidden/
// untouched), RETAIL_DUNGEON_BETA_ID has three (exercises the rebuild-and-show branch).
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.refreshSelectPickers = vi.fn();

const {CommonDungeonrouteCreateDungeonstartselect} = require('./dungeonstartselect');
const {
    RETAIL_DUNGEON_ALPHA_ID,
    RETAIL_DUNGEON_BETA_ID,
    DUNGEON_STARTS_BY_DUNGEON_ID,
} = require('../../../../../test/fixtures/createRouteForm');

// A third dungeon, absent from DUNGEON_STARTS_BY_DUNGEON_ID entirely, to exercise the "no entry at
// all" branch (dungeonStarts defaults to []).
const DUNGEON_WITHOUT_STARTS_ID = 9999;

/**
 * @param {number} selectedDungeonId
 */
function buildDom(selectedDungeonId) {
    document.body.innerHTML = `
        <select id="dungeon_id_select">
            <option value="${RETAIL_DUNGEON_ALPHA_ID}" ${selectedDungeonId === RETAIL_DUNGEON_ALPHA_ID ? 'selected' : ''}>Alpha</option>
            <option value="${RETAIL_DUNGEON_BETA_ID}" ${selectedDungeonId === RETAIL_DUNGEON_BETA_ID ? 'selected' : ''}>Beta</option>
            <option value="${DUNGEON_WITHOUT_STARTS_ID}" ${selectedDungeonId === DUNGEON_WITHOUT_STARTS_ID ? 'selected' : ''}>No starts</option>
        </select>
        <div id="dungeon_start_map_icon_id_container" style="display: none;">
            <select id="dungeon_start_map_icon_id"></select>
        </div>`;
}

/**
 * @param {number} selectedDungeonId
 * @param {number|null} [selectedDungeonStartId]
 * @returns {CommonDungeonrouteCreateDungeonstartselect}
 */
function buildAndActivate(selectedDungeonId, selectedDungeonStartId = null) {
    buildDom(selectedDungeonId);

    const code = new CommonDungeonrouteCreateDungeonstartselect('dungeonstartselect', 'common/dungeonroute/create/dungeonstartselect', {
        dungeonSelectId: '#dungeon_id_select',
        dungeonStartSelectId: '#dungeon_start_map_icon_id',
        dungeonStartContainerId: '#dungeon_start_map_icon_id_container',
        dungeonStartsByDungeonId: DUNGEON_STARTS_BY_DUNGEON_ID,
        selectedDungeonStartId,
    });
    code.activate();

    return code;
}

describe('CommonDungeonrouteCreateDungeonstartselect.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('activate_givenDungeonWithMultipleStarts_rebuildsOneOptionPerStart', () => {
        // Arrange + Act
        buildAndActivate(RETAIL_DUNGEON_BETA_ID);

        // Assert
        const options = Array.from(document.getElementById('dungeon_start_map_icon_id').options);
        const expectedStarts = DUNGEON_STARTS_BY_DUNGEON_ID[RETAIL_DUNGEON_BETA_ID];
        expect(options.map((option) => option.value)).toEqual(expectedStarts.map((start) => String(start.id)));
        expect(options.map((option) => option.text)).toEqual(expectedStarts.map((start) => start.text));
    });

    it('activate_givenDungeonWithMultipleStarts_showsStartContainer', () => {
        // Arrange + Act
        buildAndActivate(RETAIL_DUNGEON_BETA_ID);

        // Assert
        expect(document.getElementById('dungeon_start_map_icon_id_container').style.display).not.toBe('none');
    });

    it('activate_givenDungeonWithExactlyOneStart_leavesContainerHiddenWithNoOptions', () => {
        // Arrange + Act: RETAIL_DUNGEON_ALPHA_ID has exactly one dungeon start - not > 1, so the
        // "show" branch never runs.
        buildAndActivate(RETAIL_DUNGEON_ALPHA_ID);

        // Assert
        expect(document.getElementById('dungeon_start_map_icon_id_container').style.display).toBe('none');
        expect(document.getElementById('dungeon_start_map_icon_id').options.length).toBe(0);
    });

    it('activate_givenDungeonWithNoStartsEntry_leavesContainerHiddenWithNoOptions', () => {
        // Arrange + Act: dungeon id absent from dungeonStartsByDungeonId entirely - falls back to [].
        buildAndActivate(DUNGEON_WITHOUT_STARTS_ID);

        // Assert
        expect(document.getElementById('dungeon_start_map_icon_id_container').style.display).toBe('none');
        expect(document.getElementById('dungeon_start_map_icon_id').options.length).toBe(0);
    });

    it('activate_givenPreselectedDungeonStartId_marksMatchingOptionSelected', () => {
        // Arrange + Act
        const preselected = DUNGEON_STARTS_BY_DUNGEON_ID[RETAIL_DUNGEON_BETA_ID][2];
        buildAndActivate(RETAIL_DUNGEON_BETA_ID, preselected.id);

        // Assert
        expect($('#dungeon_start_map_icon_id').val()).toBe(String(preselected.id));
    });

    it('activate_givenDungeonChangedToDungeonWithFewerStarts_clearsOldOptionsBeforeRebuilding', () => {
        // Arrange
        buildAndActivate(RETAIL_DUNGEON_BETA_ID);
        expect(document.getElementById('dungeon_start_map_icon_id').options.length).toBe(3);

        // Act: switch to the dungeon with only one start.
        $('#dungeon_id_select').val(RETAIL_DUNGEON_ALPHA_ID).trigger('change');

        // Assert: options are cleared (not merged with the previous dungeon's), container hides again.
        expect(document.getElementById('dungeon_start_map_icon_id').options.length).toBe(0);
        expect(document.getElementById('dungeon_start_map_icon_id_container').style.display).toBe('none');
    });

    it('activate_givenDungeonChangedToDungeonWithMultipleStarts_clearsOldOptionAndRebuilds', () => {
        // Arrange
        buildAndActivate(RETAIL_DUNGEON_ALPHA_ID);
        expect(document.getElementById('dungeon_start_map_icon_id').options.length).toBe(0);

        // Act: switch to the dungeon with three starts.
        $('#dungeon_id_select').val(RETAIL_DUNGEON_BETA_ID).trigger('change');

        // Assert
        const options = Array.from(document.getElementById('dungeon_start_map_icon_id').options);
        expect(options.map((option) => option.value)).toEqual(
            DUNGEON_STARTS_BY_DUNGEON_ID[RETAIL_DUNGEON_BETA_ID].map((start) => String(start.id)),
        );
        expect(document.getElementById('dungeon_start_map_icon_id_container').style.display).not.toBe('none');
    });
});
