// ---------------------------------------------------------------------------
// Integration suite for the Create Route form (#3514/#3535): builds the real form DOM, activates
// the real inline JS classes + real Tom Select, drives the user actions each scenario requires, and
// asserts the resulting `new FormData(form)` output against the shared FE/BE payload fixtures in
// tests/Fixtures/CreateRoute/payloads/*.json (also consumed by the PHP contract test).
//
// Stubs, mirroring menuitemsanchor.test.js's pattern for exercising InlineCode subclasses:
//   - real jQuery on globalThis (the inline classes reference `$`/`jQuery` as bare globals)
//   - real InlineCode + real refreshSelectPickers() on globalThis (see createRouteForm.js)
//   - a Handlebars.templates.composition_icon_option_template stub (only used for `data-content`,
//     never asserted on)
//   - a KeyLevelHandler stub: ion-rangeslider is E2E territory, not something jsdom should load; the
//     hidden dungeon_route_level input simply keeps its blade-prefilled "min;max" value untouched.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');
globalThis.Handlebars = {templates: {composition_icon_option_template: () => ''}};
globalThis.KeyLevelHandler = class KeyLevelHandler {
    apply() {
    }

    update() {
    }
};

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;
const {LevelSliderInitializer} = require('../forms/shared/levelSliderInitializer');
globalThis.LevelSliderInitializer = LevelSliderInitializer;

const {
    buildCreateRouteForm,
    buildInlineOptions,
    activateCreateRouteForm,
    RETAIL_DUNGEON_ALPHA_ID,
    RETAIL_DUNGEON_BETA_ID,
    CLASSIC_DUNGEON_NONSPEEDRUN_ID,
    CLASSIC_DUNGEON_SSC_ID,
    CLASSIC_DUNGEON_TK_ID,
    TEAM_ID,
    AFFIX_GROUP_DEFAULT_ID,
    AFFIX_GROUP_ALT_A_ID,
    AFFIX_GROUP_ALT_B_ID,
    ATTRIBUTE_ALPHA_ID,
    ATTRIBUTE_BETA_ID,
    FACTION_HORDE_ID,
    CLASS_WARRIOR_ID,
    SPEC_FURY_ID,
    RACE_ORC_ID,
    KEY_LEVEL_MIN,
    KEY_LEVEL_MAX,
    DUNGEON_STARTS_BY_DUNGEON_ID,
} = require('../../../../test/fixtures/createRouteForm');
const {serializeForm, resolveFixture, loadPayloadFixtures, assertPayloadsMatch} = require('../../../../test/fixtures/payloadContract');

const TITLE = 'My Test Route';

const fixtures = loadPayloadFixtures();

/**
 * @param {HTMLElement} select
 * @param {string|number} value
 */
function selectValue(select, value) {
    select.tomselect.addItem(String(value));
}

/**
 * @param {string} dataId
 */
function clickAffixRow(dataId) {
    document.querySelector(`.affix_list_row[data-id="${dataId}"]`).click();
}

describe('createroute.serialization', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('serializeForm_givenRetailMinimalDefaults_matchesRegressionPinFixture', () => {
        // Arrange: the #3514 regression pin - a brand-new retail user who only fills in the title.
        // Everything else (dungeon, team, affixes, difficulty, dungeon start) is left at its default.
        const form    = buildCreateRouteForm({gameVersion: 'retail', hasTeams: true, dungeonIds: [RETAIL_DUNGEON_ALPHA_ID]});
        const options = buildInlineOptions({defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID]});
        form.querySelector('#dungeon_route_title').value = TITLE;

        // Act
        activateCreateRouteForm(form, options);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-minimal-defaults'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id: AFFIX_GROUP_DEFAULT_ID,
        }));
    });

    it('serializeForm_givenThreeAffixRowsClicked_seasonalIndexFollowsMajoritySelection', () => {
        // Arrange: an awakened season with 3 affix rows (two share seasonal_index 0, one is index 1).
        const form    = buildCreateRouteForm({
            gameVersion: 'retail',
            hasTeams: true,
            dungeonIds: [RETAIL_DUNGEON_ALPHA_ID],
            affixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: 0},
                {id: AFFIX_GROUP_ALT_A_ID, seasonalIndex: 0},
                {id: AFFIX_GROUP_ALT_B_ID, seasonalIndex: 1},
            ],
            withSeasonalIndexPreset: true,
        });
        const options = buildInlineOptions({defaultSelectedAffixes: []});
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        // Act: click all 3 affix rows.
        clickAffixRow(String(AFFIX_GROUP_DEFAULT_ID));
        clickAffixRow(String(AFFIX_GROUP_ALT_A_ID));
        clickAffixRow(String(AFFIX_GROUP_ALT_B_ID));

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-multiple-affixes'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id_1: AFFIX_GROUP_DEFAULT_ID,
            affix_group_id_2: AFFIX_GROUP_ALT_A_ID,
            affix_group_id_3: AFFIX_GROUP_ALT_B_ID,
        }));
    });

    it('serializeForm_givenTeamPickedViaTomSelect_includesSelectedTeamId', () => {
        // Arrange
        const form    = buildCreateRouteForm({gameVersion: 'retail', hasTeams: true, dungeonIds: [RETAIL_DUNGEON_ALPHA_ID]});
        const options = buildInlineOptions({defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID]});
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        // Act
        selectValue(document.getElementById('team_id_select'), TEAM_ID);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-with-team'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            team_id: TEAM_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id: AFFIX_GROUP_DEFAULT_ID,
        }));
    });

    it('serializeForm_givenUserHasNoTeams_omitsTeamIdEntirely', () => {
        // Arrange: common/team/select.blade.php renders no <select> at all when the teams collection
        // is empty - team_id must be entirely absent, not defaulted to -1.
        const form    = buildCreateRouteForm({gameVersion: 'retail', hasTeams: false, dungeonIds: [RETAIL_DUNGEON_ALPHA_ID]});
        const options = buildInlineOptions({defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID]});
        form.querySelector('#dungeon_route_title').value = TITLE;

        // Act
        activateCreateRouteForm(form, options);

        // Assert
        expect(form.querySelector('#team_id_select')).toBeNull();
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-no-teams'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id: AFFIX_GROUP_DEFAULT_ID,
        }));
    });

    it('serializeForm_givenFullGroupCompositionFilledForFirstPartyMember_submitsChosenIdsForSlotOneOnly', () => {
        // Arrange
        const form    = buildCreateRouteForm({gameVersion: 'retail', hasTeams: true, dungeonIds: [RETAIL_DUNGEON_ALPHA_ID]});
        const options = buildInlineOptions({defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID]});
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        // Act: fill in party member #1's faction, race, class and specialization via Tom Select.
        selectValue(document.getElementById('faction_id'), FACTION_HORDE_ID);
        selectValue(document.querySelector('.raceselect[data-id="1"]'), RACE_ORC_ID);
        selectValue(document.querySelector('.classselect[data-id="1"]'), CLASS_WARRIOR_ID);
        selectValue(document.querySelector('.specializationselect[data-id="1"]'), SPEC_FURY_ID);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-full-composition'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id: AFFIX_GROUP_DEFAULT_ID,
            faction_id: FACTION_HORDE_ID,
            class_id: CLASS_WARRIOR_ID,
            specialization_id: SPEC_FURY_ID,
            race_id: RACE_ORC_ID,
        }));
    });

    it('serializeForm_givenTwoAttributesSelected_includesBothAttributeIds', () => {
        // Arrange
        const form    = buildCreateRouteForm({gameVersion: 'retail', hasTeams: true, dungeonIds: [RETAIL_DUNGEON_ALPHA_ID]});
        const options = buildInlineOptions({defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID]});
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        // Act
        selectValue(document.getElementById('attributes'), ATTRIBUTE_ALPHA_ID);
        selectValue(document.getElementById('attributes'), ATTRIBUTE_BETA_ID);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-attributes'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id: AFFIX_GROUP_DEFAULT_ID,
            attribute_id_1: ATTRIBUTE_ALPHA_ID,
            attribute_id_2: ATTRIBUTE_BETA_ID,
        }));
    });

    it('serializeForm_givenDungeonWithMultipleStarts_rebuildsAndSubmitsChosenStart', () => {
        // Arrange: the default dungeon has 3 dungeon starts, so the select is rebuilt and shown.
        const form    = buildCreateRouteForm({
            gameVersion: 'retail',
            hasTeams: true,
            dungeonIds: [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID],
            selectedDungeonId: RETAIL_DUNGEON_BETA_ID,
        });
        const options = buildInlineOptions({defaultSelectedAffixes: [AFFIX_GROUP_DEFAULT_ID]});
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        const chosenStart = DUNGEON_STARTS_BY_DUNGEON_ID[RETAIL_DUNGEON_BETA_ID][2];

        // Act
        selectValue(document.getElementById('dungeon_start_map_icon_id'), chosenStart.id);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['retail-multiple-start-icons'], {
            dungeon_id: RETAIL_DUNGEON_BETA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
            affix_group_id: AFFIX_GROUP_DEFAULT_ID,
            dungeon_start_map_icon_id: chosenStart.id,
        }));
    });

    it('serializeForm_givenClassicNonSpeedrunDungeon_omitsLevelAndAffixesAndSubmitsUntouchedSelectsAsEmpty', () => {
        // Arrange: classic (has_seasons = false) - no level input, no affix picker, no seasonal_index.
        const form    = buildCreateRouteForm({
            gameVersion: 'classic',
            hasTeams: true,
            dungeonIds: [CLASSIC_DUNGEON_NONSPEEDRUN_ID],
        });
        const options = buildInlineOptions();
        form.querySelector('#dungeon_route_title').value = TITLE;

        // Act
        activateCreateRouteForm(form, options);

        // Assert
        expect(form.querySelector('#dungeon_route_level')).toBeNull();
        expect(form.querySelector('#route_select_affixes')).toBeNull();
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['classic-nonspeedrun'], {
            dungeon_id: CLASSIC_DUNGEON_NONSPEEDRUN_ID,
        }));
    });

    it('serializeForm_givenSpeedrunDungeonWithBothDifficultiesUntouched_autoSelectsFirstEnabledDifficulty', () => {
        // Arrange: SSC-like dungeon, both 10-man and 25-man enabled, left untouched.
        const form    = buildCreateRouteForm({gameVersion: 'classic', hasTeams: true, dungeonIds: [CLASSIC_DUNGEON_SSC_ID]});
        const options = buildInlineOptions();
        form.querySelector('#dungeon_route_title').value = TITLE;

        // Act
        activateCreateRouteForm(form, options);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['classic-speedrun-ssc-10man'], {
            dungeon_id: CLASSIC_DUNGEON_SSC_ID,
        }));
    });

    it('serializeForm_givenSpeedrunDungeonWith25ManExplicitlyChosen_submitsChosenDifficulty', () => {
        // Arrange
        const form    = buildCreateRouteForm({gameVersion: 'classic', hasTeams: true, dungeonIds: [CLASSIC_DUNGEON_SSC_ID]});
        const options = buildInlineOptions();
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        // Act
        selectValue(document.getElementById('dungeon_difficulty_select'), 2);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['classic-speedrun-ssc-25man'], {
            dungeon_id: CLASSIC_DUNGEON_SSC_ID,
        }));
    });

    it('serializeForm_givenSpeedrunDungeonWithOnly25ManEnabled_autoSelectsTheSoleDifficulty', () => {
        // Arrange: Tempest Keep-like dungeon, only 25-man enabled, left untouched.
        const form    = buildCreateRouteForm({gameVersion: 'classic', hasTeams: true, dungeonIds: [CLASSIC_DUNGEON_TK_ID]});
        const options = buildInlineOptions();
        form.querySelector('#dungeon_route_title').value = TITLE;

        // Act
        activateCreateRouteForm(form, options);

        // Assert
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['classic-speedrun-tempest-keep-default'], {
            dungeon_id: CLASSIC_DUNGEON_TK_ID,
        }));
    });

    it('serializeForm_givenSpeedrunDungeonSwitchedToNonSpeedrun_clearsDifficultyInsteadOfSubmittingStaleValue', () => {
        // Arrange: regression test for GitHub issue #3535. dungeondifficultyselect.js used to only hide
        // the container on switch-away from a speedrun dungeon, leaving the select's stale options/
        // selection in place, so the hidden <select> would still submit the stale value from the
        // previously-selected dungeon. It now also clears the options.
        const form    = buildCreateRouteForm({
            gameVersion: 'classic',
            hasTeams: true,
            dungeonIds: [CLASSIC_DUNGEON_SSC_ID, CLASSIC_DUNGEON_NONSPEEDRUN_ID],
            selectedDungeonId: CLASSIC_DUNGEON_SSC_ID,
        });
        const options = buildInlineOptions();
        form.querySelector('#dungeon_route_title').value = TITLE;
        activateCreateRouteForm(form, options);

        // Sanity check on the arrange step: SSC starts out on difficulty "1" (10-man, first enabled).
        expect(document.getElementById('dungeon_difficulty_select').value).toBe('1');

        // Act: switch away to a non-speedrun dungeon.
        selectValue(document.getElementById('dungeon_id_select'), CLASSIC_DUNGEON_NONSPEEDRUN_ID);

        // Assert
        expect(document.getElementById('dungeon_difficulty_select_container').style.display).toBe('none');
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['classic-speedrun-then-switch'], {
            dungeon_id: CLASSIC_DUNGEON_NONSPEEDRUN_ID,
        }));
    });

    it('serializeForm_givenGuestTemporaryRouteUntouched_matchesMinimalTemporaryFixture', () => {
        // Arrange: createroutetemporary.blade.php - no team/affix/attribute/composition pickers.
        const form    = buildCreateRouteForm({temporary: true, gameVersion: 'retail', dungeonIds: [RETAIL_DUNGEON_ALPHA_ID]});
        const options = buildInlineOptions({temporary: true});

        // Act
        activateCreateRouteForm(form, options);

        // Assert
        expect(form.querySelector('#team_id_select')).toBeNull();
        expect(form.querySelector('#route_select_affixes')).toBeNull();
        expect(form.querySelector('#attributes')).toBeNull();
        expect(form.querySelector('#faction_id')).toBeNull();
        assertPayloadsMatch(serializeForm(form), resolveFixture(fixtures['temporary-guest-minimal'], {
            dungeon_id: RETAIL_DUNGEON_ALPHA_ID,
            key_level_min: KEY_LEVEL_MIN,
            key_level_max: KEY_LEVEL_MAX,
        }));
    });
});
