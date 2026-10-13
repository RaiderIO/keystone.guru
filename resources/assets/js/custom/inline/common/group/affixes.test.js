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
 * @param {{id: number, seasonalIndex: number|null}[]} config.affixGroups
 * @param {number[]} config.defaultSelectedAffixes
 * @param {boolean} [config.withSeasonalIndexPreset]
 * @param {boolean} [config.editingExistingRoute] - see the note on `_automaticSeasonalIndexChange`
 *   below: pass true to exercise the seasonal_index auto-select feature at all.
 * @param {Object<number, number>} [config.seasonalIndexOverrides] - override `seasonal_index` per
 *   affix group id (see the allAffixGroups note below).
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
    const options = buildInlineOptions({defaultSelectedAffixes}).affixes;

    // affixes.js only flips on its automatic seasonal_index rewrite (`_automaticSeasonalIndexChange`)
    // when `options.dungeonroute` is set, i.e. when *editing* an existing route - never when creating
    // one (dungeonroute is always null for /routes/new, which is what buildInlineOptions() defaults
    // to). So in the create-route flow the "Awakened Enemy Set" preset never follows affix clicks at
    // all; it stays at whatever the blade rendered it to (0, absent an existing route). Tests below
    // cover both: the real create-flow no-op, and the edit-flow auto-select feature itself.
    options.dungeonroute = editingExistingRoute ? {} : null;

    // _getAffixGroupById() checks currentSeason/nextSeason first but then *unconditionally*
    // re-checks allAffixGroups and overwrites the result if found there too - so for any affix group
    // id present in both (as is always true here), allAffixGroups is what actually wins. In
    // production allAffixGroups is a full AffixGroup Eloquent model dump and does carry
    // seasonal_index; buildInlineOptions()'s fixture simplifies it down to {id, expansion_id} only.
    // Enrich it here (in-test only, not touching the shared fixture) so the auto-select behaviour
    // under test reflects the real seasonal_index each affix group carries in production, rather than
    // silently no-op'ing on `undefined`.
    const seasonalIndexById = Object.fromEntries(options.currentSeason.affix_groups.map((group) => [group.id, group.seasonal_index]));
    options.allAffixGroups = options.allAffixGroups.map((group) => ({
        ...group,
        seasonal_index: Object.prototype.hasOwnProperty.call(seasonalIndexOverrides, group.id)
            ? seasonalIndexOverrides[group.id]
            : seasonalIndexById[group.id],
    }));

    beforeActivate(options);

    const code = new CommonGroupAffixes('affixes', 'common/group/affixes', options);
    code.activate();

    return code;
}

describe('CommonGroupAffixes.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('activate_givenUnselectedAffixRowClicked_addsItsIdToTheHiddenMultiselect', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}, {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null}],
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
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}, {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null}],
            defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID, AFFIX_GROUP_ALT_A_ID],
        });
        expect($('#route_select_affixes').val()).toEqual(expect.arrayContaining([String(AFFIX_GROUP_DEFAULT_ID), String(AFFIX_GROUP_ALT_A_ID)]));

        // Act: click the already-selected row again to deselect it.
        clickAffixRow(AFFIX_GROUP_ALT_A_ID);

        // Assert
        expect($('#route_select_affixes').val()).toEqual([String(AFFIX_GROUP_DEFAULT_ID)]);
        expect(document.querySelector(`.affix_list_row[data-id="${AFFIX_GROUP_ALT_A_ID}"]`).classList).not.toContain('affix_list_row_selected');
    });

    it('activate_givenCreatingNewRoute_affixRowClicksNeverTouchSeasonalIndex', () => {
        // Arrange: options.dungeonroute is null in the create flow, so _automaticSeasonalIndexChange
        // is false and _applyAffixRowSelection's seasonal_index block never runs. Force the select
        // away from its rendered default first so the assertion proves a real no-op, not a coincidence.
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_B_ID, seasonalIndex: null},
            ],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            editingExistingRoute: false,
        });
        $('#seasonal_index').val('2');

        // Act
        clickAffixRow(AFFIX_GROUP_DEFAULT_ID);
        clickAffixRow(AFFIX_GROUP_ALT_A_ID);
        clickAffixRow(AFFIX_GROUP_ALT_B_ID);

        // Assert
        expect($('#seasonal_index').val()).toBe('2');
    });

    it('activate_givenEditingExistingRouteAndMultipleRowsSharingASeasonalIndexClicked_autoSelectsThatSeasonalIndexAsMajority', () => {
        // Arrange: two rows share seasonal_index 0, one is index 1 - majority wins. Only reachable
        // when editing an existing route (see the note in buildAndActivate).
        buildAndActivate({
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_B_ID, seasonalIndex: null},
            ],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            editingExistingRoute: true,
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

    it('activate_givenEditingExistingRouteAndSingleRowClicked_setsSeasonalIndexToThatRowsOwnIndex', () => {
        // Arrange
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}, {id: AFFIX_GROUP_ALT_B_ID, seasonalIndex: null}],
            defaultSelectedAffixes: [],
            withSeasonalIndexPreset: true,
            editingExistingRoute: true,
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

    it('activate_givenADungeonOfTheCurrentSeason_showsOnlyThatSeasonsAffixRows', () => {
        // Arrange + Act
        buildAndActivate({
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}, {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null}],
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
                {id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_B_ID, seasonalIndex: null},
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
                {id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null},
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
                {id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null},
                {id: AFFIX_GROUP_ALT_B_ID, seasonalIndex: null},
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
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}, {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null}],
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
            affixGroups: [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}, {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: null}],
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
