// ---------------------------------------------------------------------------
// CommonDungeonrouteCreateDungeondifficultyselect is a global-script style class: it extends the
// bare global `InlineCode` and uses `$`/`jQuery`, `lang.get` (stubbed by test/setup.js to return the
// translation key) and `refreshSelectPickers` (stubbed here with a spy - the real Tom Select
// integration is exercised by the full-form integration suite at
// custom/inline/common/forms/createroute.serialization.test.js) at runtime.
//
// DOM is a minimal subset of the Create Route form (just the dungeon + difficulty selects), reusing
// the sentinel dungeon ids and difficultyByDungeon shape from test/fixtures/createRouteForm.js so the
// scenarios line up with the shared fixture (SSC-like: both difficulties enabled; TK-like: only
// 25-man enabled).
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.refreshSelectPickers = vi.fn();

const {CommonDungeonrouteCreateDungeondifficultyselect} = require('./dungeondifficultyselect');
const {
    CLASSIC_DUNGEON_SSC_ID,
    CLASSIC_DUNGEON_TK_ID,
    CLASSIC_DUNGEON_NONSPEEDRUN_ID,
    SPEEDRUN_DUNGEON_IDS,
    DIFFICULTY_BY_DUNGEON,
    DIFFICULTY_10_MAN,
    DIFFICULTY_25_MAN,
} = require('../../../../../test/fixtures/createRouteForm');

/**
 * @param {number} selectedDungeonId
 * @returns {HTMLFormElement}
 */
function buildDom(selectedDungeonId) {
    document.body.innerHTML = `
        <select id="dungeon_id_select">
            <option value="${CLASSIC_DUNGEON_SSC_ID}" ${selectedDungeonId === CLASSIC_DUNGEON_SSC_ID ? 'selected' : ''}>SSC</option>
            <option value="${CLASSIC_DUNGEON_TK_ID}" ${selectedDungeonId === CLASSIC_DUNGEON_TK_ID ? 'selected' : ''}>Tempest Keep</option>
            <option value="${CLASSIC_DUNGEON_NONSPEEDRUN_ID}" ${selectedDungeonId === CLASSIC_DUNGEON_NONSPEEDRUN_ID ? 'selected' : ''}>Non-speedrun</option>
        </select>
        <div id="dungeon_difficulty_select_container" style="display: none;">
            <select id="dungeon_difficulty_select"></select>
        </div>`;
    return document.getElementById('dungeon_id_select');
}

/**
 * @returns {CommonDungeonrouteCreateDungeondifficultyselect}
 */
function buildAndActivate(selectedDungeonId) {
    buildDom(selectedDungeonId);

    const code = new CommonDungeonrouteCreateDungeondifficultyselect('dungeondifficultyselect', 'common/dungeonroute/create/dungeondifficultyselect', {
        dungeonSelectSelector: '#dungeon_id_select',
        dungeonDifficultySelectSelector: '#dungeon_difficulty_select',
        dungeonDifficultySelectContainerSelector: '#dungeon_difficulty_select_container',
        speedrunDungeonIds: SPEEDRUN_DUNGEON_IDS,
        difficultyByDungeon: DIFFICULTY_BY_DUNGEON,
    });
    code.activate();

    return code;
}

describe('CommonDungeonrouteCreateDungeondifficultyselect.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('activate_givenSpeedrunDungeonWithBothDifficultiesEnabled_rebuildsOneOptionPerEnabledDifficulty', () => {
        // Arrange + Act
        buildAndActivate(CLASSIC_DUNGEON_SSC_ID);

        // Assert
        const options = Array.from(document.getElementById('dungeon_difficulty_select').options);
        expect(options.map((option) => option.value)).toEqual([String(DIFFICULTY_10_MAN), String(DIFFICULTY_25_MAN)]);
    });

    it('activate_givenSpeedrunDungeonWithOnlyOneDifficultyEnabled_rebuildsOnlyThatOption', () => {
        // Arrange + Act
        buildAndActivate(CLASSIC_DUNGEON_TK_ID);

        // Assert: Tempest Keep only has 25-man enabled.
        const options = Array.from(document.getElementById('dungeon_difficulty_select').options);
        expect(options.map((option) => option.value)).toEqual([String(DIFFICULTY_25_MAN)]);
    });

    it('activate_givenSpeedrunDungeon_labelsOptionsViaLangGetWithDifficultyKey', () => {
        // Arrange + Act: lang.get is stubbed (test/setup.js) to return the key itself.
        buildAndActivate(CLASSIC_DUNGEON_SSC_ID);

        // Assert
        const options = Array.from(document.getElementById('dungeon_difficulty_select').options);
        expect(options.map((option) => option.text)).toEqual([
            `dungeons.difficulty.${DIFFICULTY_10_MAN}`,
            `dungeons.difficulty.${DIFFICULTY_25_MAN}`,
        ]);
    });

    it('activate_givenSpeedrunDungeonSelected_showsDifficultyContainer', () => {
        // Arrange + Act
        buildAndActivate(CLASSIC_DUNGEON_SSC_ID);

        // Assert
        expect(document.getElementById('dungeon_difficulty_select_container').style.display).not.toBe('none');
    });

    it('activate_givenNonSpeedrunDungeonSelected_leavesDifficultyContainerHiddenAndOptionless', () => {
        // Arrange + Act
        buildAndActivate(CLASSIC_DUNGEON_NONSPEEDRUN_ID);

        // Assert
        expect(document.getElementById('dungeon_difficulty_select_container').style.display).toBe('none');
        expect(document.getElementById('dungeon_difficulty_select').options.length).toBe(0);
    });

    it('activate_givenDungeonChangedToDifferentSpeedrunDungeon_rebuildsOptionsForTheNewDungeon', () => {
        // Arrange
        buildAndActivate(CLASSIC_DUNGEON_SSC_ID);

        // Act: switch from SSC (both difficulties) to Tempest Keep (25-man only).
        $('#dungeon_id_select').val(CLASSIC_DUNGEON_TK_ID).trigger('change');

        // Assert
        const options = Array.from(document.getElementById('dungeon_difficulty_select').options);
        expect(options.map((option) => option.value)).toEqual([String(DIFFICULTY_25_MAN)]);
        expect(document.getElementById('dungeon_difficulty_select_container').style.display).not.toBe('none');
    });

    it('activate_givenDungeonChangedFromSpeedrunToNonSpeedrun_hidesContainerAndClearsOptionsAndSelection', () => {
        // See GitHub issue #3535: dungeonSelectionChanged() used to only *hide* the container on
        // switch-away from a speedrun dungeon, leaving the difficulty select's stale options/selection
        // in place (a hidden <select> is still a successful form control, so the stale value would be
        // submitted). It now also clears the options so nothing stale can be submitted.
        // Arrange
        buildAndActivate(CLASSIC_DUNGEON_SSC_ID);
        const $difficultySelect = $('#dungeon_difficulty_select');
        // Sanity check on the arrange step: SSC starts out on difficulty "1" (10-man, first option).
        expect($difficultySelect.val()).toBe(String(DIFFICULTY_10_MAN));

        // Act: switch away to a non-speedrun dungeon.
        $('#dungeon_id_select').val(CLASSIC_DUNGEON_NONSPEEDRUN_ID).trigger('change');

        // Assert
        expect(document.getElementById('dungeon_difficulty_select_container').style.display).toBe('none');
        expect(document.getElementById('dungeon_difficulty_select').options.length).toBe(0);
    });
});
