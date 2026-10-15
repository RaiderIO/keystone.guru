// ---------------------------------------------------------------------------
// CommonGroupAffixes is a global-script style class: it extends the bare global `InlineCode` and
// uses `$`/`jQuery` and `refreshSelectPickers` (stubbed here with a spy - the real Tom Select
// integration is exercised by the full-form integration suite at
// custom/inline/common/forms/createroute.serialization.test.js) at runtime.
//
// DOM/options come from the shared test/fixtures/createRouteForm.js builders (buildCreateRouteForm +
// buildInlineOptions) rather than a hand-rolled subset: the affix row click handler is entangled with
// the dungeon select (_dungeonChanged filters rows by expansion/season CSS classes), so reusing the
// fixture's proven-correct markup avoids subtly wrong test setup.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.refreshSelectPickers = vi.fn();

const {CommonGroupAffixes} = require('./affixes');
const {
    buildCreateRouteForm,
    buildInlineOptions,
    RETAIL_DUNGEON_ALPHA_ID,
    AFFIX_GROUP_DEFAULT_ID,
    AFFIX_GROUP_ALT_A_ID,
    AFFIX_GROUP_ALT_B_ID,
    RETAIL_DUNGEON_BETA_ID,
    RETAIL_EXPANSION_ID,
    RETAIL_EXPANSION_SHORTNAME,
    CURRENT_SEASON_ID,
} = require('#test/fixtures/createRouteForm');

/**
 * @param {string|number} dataId
 */
function clickAffixRow(dataId) {
    document.querySelector(`.affix_list_row[data-id="${dataId}"]`).click();
}

/**
 * @param {number} dungeonId
 */
function pickDungeon(dungeonId) {
    $('#dungeon_id_select').val(String(dungeonId)).trigger('change');
}

/**
 * @param {number} seasonId
 * @param {number} affixGroupId
 * @returns {string}
 */
function affixRowHtml(seasonId, affixGroupId) {
    return `<div class="row affix_list_row expansion ${RETAIL_EXPANSION_SHORTNAME} season season-${seasonId}" data-id="${affixGroupId}"><i class="check"></i></div>`;
}

/**
 * @param {string} selector
 * @returns {string[]}
 */
function displayOf(selector) {
    return Array.from(document.querySelectorAll(selector)).map((row) => row.style.display);
}

/**
 * @param {Object} config
 * @param {{id: number}[]} config.affixGroups
 * @param {number[]} config.defaultSelectedAffixes
 * @param {boolean} [config.withSeasonalIndexPreset]
 * @param {boolean} [config.editingExistingRoute] - sets `options.dungeonroute`, which turns off the automatic
 *   seasonal_index preset selection.
 * @param {Object<number, number>} [config.seasonalIndexOverrides] - override `seasonal_index` per affix group id.
 * @returns {CommonGroupAffixes}
 */
function buildAndActivate({
    affixGroups,
    defaultSelectedAffixes,
    withSeasonalIndexPreset = false,
    editingExistingRoute = false,
    seasonalIndexOverrides = {},
    dungeonIds = [RETAIL_DUNGEON_ALPHA_ID],
    beforeActivate = () => {},
}) {
    buildCreateRouteForm({
        gameVersion: 'retail',
        dungeonIds,
        affixGroups,
        withSeasonalIndexPreset,
    });
    const seasonAffixGroups = buildInlineOptions().affixes.currentSeason.affix_groups.map((group) => ({
        id: group.id,
        seasonalIndex: Object.prototype.hasOwnProperty.call(seasonalIndexOverrides, group.id)
            ? seasonalIndexOverrides[group.id]
            : group.seasonal_index,
    }));
    const options = buildInlineOptions({defaultSelectedAffixes, seasonAffixGroups}).affixes;
    options.dungeonroute = editingExistingRoute ? {} : null;

    beforeActivate(options);

    const code = new CommonGroupAffixes('affixes', 'common/group/affixes', options);
    code.activate();

    return code;
}

/**
 * Picks a preset the way a user does: Tom Select (or the native select) fires `change` on the <select>.
 *
 * @param {string} value
 */
function pickSeasonalIndexByHand(value) {
    const select = document.querySelector('#seasonal_index');
    select.value = value;
    select.dispatchEvent(new Event('change', {bubbles: true}));
}

describe('CommonGroupAffixes.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('activate_givenUnselectedAffixRowClicked_addsItsIdToTheHiddenMultiselect', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_A_ID}],
            defaultSelectedAffixes: [],
        });

        // Act
        clickAffixRow(AFFIX_GROUP_ALT_A_ID);

        // Assert
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_ALT_A_ID)]);
        expect(document.querySelector(`.affix_list_row[data-id="${AFFIX_GROUP_ALT_A_ID}"]`).classList).toContain('affix_list_row_selected');
    });

    it('activate_givenSelectedAffixRowClickedAgain_removesItsIdFromTheHiddenMultiselect', () => {
        // Arrange: pre-selected via defaultSelected, mirroring the initial page load.
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_A_ID}],
            defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID, AFFIX_GROUP_ALT_A_ID],
        });
        expect($('#route_select_affixes').val()).toEqual(expect.arrayContaining([String(AFFIX_GROUP_DEFAULT_ID), String(AFFIX_GROUP_ALT_A_ID)]));

        // Act: click the already-selected row again to deselect it.
        clickAffixRow(AFFIX_GROUP_ALT_A_ID);

        // Assert
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_DEFAULT_ID)]);
        expect(document.querySelector(`.affix_list_row[data-id="${AFFIX_GROUP_ALT_A_ID}"]`).classList).not.toContain('affix_list_row_selected');
    });

    it('activate_givenEditingExistingRoute_affixRowClicksNeverTouchSeasonalIndex', () => {
        // Arrange: an existing route keeps the preset it was saved with. Force the select away from its
        // rendered default first so the assertion proves a real no-op, not a coincidence.
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID},
                {id: AFFIX_GROUP_ALT_A_ID},
                {id: AFFIX_GROUP_ALT_B_ID},
            ],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            editingExistingRoute: true,
            seasonalIndexOverrides: {[AFFIX_GROUP_DEFAULT_ID]: 0, [AFFIX_GROUP_ALT_A_ID]: 0, [AFFIX_GROUP_ALT_B_ID]: 1},
        });
        $('#seasonal_index').val('2');

        // Act
        clickAffixRow(AFFIX_GROUP_DEFAULT_ID);
        clickAffixRow(AFFIX_GROUP_ALT_A_ID);
        clickAffixRow(AFFIX_GROUP_ALT_B_ID);

        // Assert
        expect($('#seasonal_index').val()).toBe('2');
    });

    it('activate_givenCreatingNewRouteAndMultipleRowsSharingASeasonalIndexClicked_autoSelectsThatSeasonalIndexAsMajority', () => {
        // Arrange: two rows share seasonal_index 0, one is index 1 - majority wins.
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID},
                {id: AFFIX_GROUP_ALT_A_ID},
                {id: AFFIX_GROUP_ALT_B_ID},
            ],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            seasonalIndexOverrides: {[AFFIX_GROUP_DEFAULT_ID]: 0, [AFFIX_GROUP_ALT_A_ID]: 0, [AFFIX_GROUP_ALT_B_ID]: 1},
        });
        // Force the select away from its "majority winner" value first, so the assertion below
        // actually proves the class wrote it rather than coincidentally matching an untouched default.
        $('#seasonal_index').val('2');

        // Act
        clickAffixRow(AFFIX_GROUP_DEFAULT_ID);
        clickAffixRow(AFFIX_GROUP_ALT_A_ID);
        clickAffixRow(AFFIX_GROUP_ALT_B_ID);

        // Assert
        expect($('#seasonal_index').val()).toBe('0');
    });

    it('activate_givenCreatingNewRouteAndSingleRowClicked_setsSeasonalIndexToThatRowsOwnIndex', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_B_ID}],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            seasonalIndexOverrides: {[AFFIX_GROUP_ALT_B_ID]: 1},
        });
        // Force the select away from '1' first, so the assertion below actually proves the class
        // wrote it rather than coincidentally matching an untouched default.
        $('#seasonal_index').val('2');

        // Act
        clickAffixRow(AFFIX_GROUP_ALT_B_ID);

        // Assert
        expect($('#seasonal_index').val()).toBe('1');
    });

    it('activate_givenCreatingNewRouteWithMoreSelectedGroupsWithoutASeasonalIndex_followsTheGroupsThatHaveOne', () => {
        // Arrange: the page pre-selects this week's affix group of every expansion, and those carry no
        // seasonal_index. They outnumber the one clicked row that does.
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID},
                {id: AFFIX_GROUP_ALT_A_ID},
                {id: AFFIX_GROUP_ALT_B_ID},
            ],
            defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID, AFFIX_GROUP_ALT_A_ID],
            withSeasonalIndexPreset: true,
            seasonalIndexOverrides: {[AFFIX_GROUP_DEFAULT_ID]: null, [AFFIX_GROUP_ALT_A_ID]: null, [AFFIX_GROUP_ALT_B_ID]: 1},
        });
        $('#seasonal_index').val('2');

        // Act
        clickAffixRow(AFFIX_GROUP_ALT_B_ID);

        // Assert
        expect($('#seasonal_index').val()).toBe('1');
    });

    it('activate_givenCreatingNewRouteAndPresetPickedByHand_stopsFollowingAffixClicks', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_B_ID}],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            seasonalIndexOverrides: {[AFFIX_GROUP_ALT_B_ID]: 1},
        });
        pickSeasonalIndexByHand('2');

        // Act
        clickAffixRow(AFFIX_GROUP_ALT_B_ID);

        // Assert
        expect($('#seasonal_index').val()).toBe('2');
    });

    it('activate_givenADungeonOfTheCurrentSeason_showsOnlyThatSeasonsAffixRows', () => {
        // Arrange + Act
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_A_ID}],
            defaultSelectedAffixes: [],
            beforeActivate: () => $('#route_select_affixes_list_custom').append(affixRowHtml(CURRENT_SEASON_ID + 1, AFFIX_GROUP_ALT_B_ID)),
        });

        // Assert
        expect(displayOf(`.affix_list_row.season-${CURRENT_SEASON_ID}`)).toHaveLength(2);
        expect(displayOf(`.affix_list_row.season-${CURRENT_SEASON_ID}`)).not.toContain('none');
        expect(displayOf(`.affix_list_row.season-${CURRENT_SEASON_ID + 1}`)).toEqual(['none']);
    });
});

describe('CommonGroupAffixes._dungeonChanged', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
        vi.clearAllMocks();
    });

    it('dungeonChanged_givenAnotherDungeonOfTheCurrentSeason_selectsThisWeeksAffixGroup', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID},
                {id: AFFIX_GROUP_ALT_A_ID},
                {id: AFFIX_GROUP_ALT_B_ID},
            ],
            defaultSelectedAffixes: [AFFIX_GROUP_ALT_A_ID],
            dungeonIds: [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID],
            beforeActivate: (options) => {
                options.currentAffixes = {[RETAIL_EXPANSION_SHORTNAME]: {id: AFFIX_GROUP_ALT_B_ID}};
            },
        });

        // Act
        pickDungeon(RETAIL_DUNGEON_BETA_ID);

        // Assert
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_ALT_B_ID)]);
        expect(document.querySelector(`.affix_list_row[data-id="${AFFIX_GROUP_ALT_A_ID}"]`).classList).not.toContain('affix_list_row_selected');
    });

    it('dungeonChanged_givenAnotherDungeonOfTheCurrentSeasonWithoutAnAffixThisWeek_selectsTheSeasonsFirstAffixGroup', () => {
        // Arrange - the fixture has no affix group for this week
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID},
                {id: AFFIX_GROUP_ALT_A_ID},
            ],
            defaultSelectedAffixes: [AFFIX_GROUP_ALT_A_ID],
            dungeonIds: [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID],
        });

        // Act
        pickDungeon(RETAIL_DUNGEON_BETA_ID);

        // Assert
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_DEFAULT_ID)]);
    });

    it('dungeonChanged_givenADungeonOfTheNextSeason_selectsAndShowsTheNextSeasonsAffixGroups', () => {
        // Arrange
        const nextSeasonId = CURRENT_SEASON_ID + 1;
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID},
                {id: AFFIX_GROUP_ALT_A_ID},
                {id: AFFIX_GROUP_ALT_B_ID},
            ],
            defaultSelectedAffixes: [AFFIX_GROUP_ALT_A_ID],
            dungeonIds: [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID],
            beforeActivate: (options) => {
                options.nextSeason = {
                    id:           nextSeasonId,
                    expansion:    {shortname: RETAIL_EXPANSION_SHORTNAME},
                    dungeons:     [{id: RETAIL_DUNGEON_BETA_ID}],
                    affix_groups: [{id: AFFIX_GROUP_ALT_B_ID, expansion_id: RETAIL_EXPANSION_ID, seasonal_index: null}],
                };
                $('#route_select_affixes_list_custom').append(affixRowHtml(nextSeasonId, AFFIX_GROUP_ALT_B_ID));
            },
        });

        // Act
        pickDungeon(RETAIL_DUNGEON_BETA_ID);

        // Assert
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_ALT_B_ID)]);
        expect(displayOf(`.affix_list_row.season-${nextSeasonId}`)).toEqual(['']);
        expect(displayOf(`.affix_list_row.season-${CURRENT_SEASON_ID}`)).toEqual(['none', 'none', 'none']);
    });

    it('dungeonChanged_givenADungeonWithoutAKnownExpansion_leavesTheAffixesAsTheyWere', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_A_ID}],
            defaultSelectedAffixes: [AFFIX_GROUP_ALT_A_ID],
        });
        $('#dungeon_id_select').append('<option value="12345">Unknown</option>');
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        // Act
        pickDungeon(12345);

        // Assert
        expect(displayOf('.affix_list_row')).not.toContain('none');
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_ALT_A_ID)]);
        expect(warn).toHaveBeenCalledTimes(1);
    });

    it('overrideSelection_givenADungeonAndAffixGroups_selectsBoth', () => {
        // Arrange
        const code = buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID}, {id: AFFIX_GROUP_ALT_A_ID}],
            defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID],
            dungeonIds: [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID],
        });

        // Act
        code.overrideSelection(RETAIL_DUNGEON_BETA_ID, [AFFIX_GROUP_ALT_A_ID]);

        // Assert
        expect($('#dungeon_id_select').val()).toBe(String(RETAIL_DUNGEON_BETA_ID));
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_ALT_A_ID)]);
        expect(document.querySelector(`.affix_list_row[data-id="${AFFIX_GROUP_ALT_A_ID}"]`).classList).toContain('affix_list_row_selected');
        expect(document.querySelector(`.affix_list_row[data-id="${AFFIX_GROUP_DEFAULT_ID}"]`).classList).not.toContain('affix_list_row_selected');
    });
});
