// ---------------------------------------------------------------------------
// Hand-written DOM + options builder for the Create Route form (common/forms/createroute.blade.php
// and its temporary/guest variant createroutetemporary.blade.php), used by the integration
// suite at custom/inline/common/forms/createroute.serialization.test.js.
//
// The markup below mirrors the exact name/id/class attributes the blade partials render (see the
// list of blade files read while writing this fixture); it does not re-implement their PHP-side
// grouping/eager-loading logic. All ids that would normally come from the seeded database (dungeon,
// team, affix group, attribute, faction/class/specialization/race ids) are sentinel constants
// exported below, so tests can assert on and interact with known values.
// ---------------------------------------------------------------------------

// ---- Sentinel dungeon ids -------------------------------------------------
// Retail: never speedrun. 9001 has exactly one dungeon start (stays untouched/hidden); 9002 has
// three (exercises the "rebuild + show" branch of dungeonstartselect.js).
const RETAIL_DUNGEON_ALPHA_ID = 9001;
const RETAIL_DUNGEON_BETA_ID  = 9002;
// Classic: 9101 is a plain (non-speedrun) dungeon. 9201/9202 are speedrun raids akin to Serpentshrine
// Cavern (both difficulties enabled) and Tempest Keep (only 25-man enabled).
const CLASSIC_DUNGEON_NONSPEEDRUN_ID = 9101;
const CLASSIC_DUNGEON_SSC_ID          = 9201;
const CLASSIC_DUNGEON_TK_ID           = 9202;

const SPEEDRUN_DUNGEON_IDS = [CLASSIC_DUNGEON_SSC_ID, CLASSIC_DUNGEON_TK_ID];

// DungeonConstants::DIFFICULTY_ALL = ['10_man' => 1, '25_man' => 2] - identical in every environment.
const DIFFICULTY_10_MAN = 1;
const DIFFICULTY_25_MAN = 2;

const DIFFICULTY_BY_DUNGEON = {
    [CLASSIC_DUNGEON_SSC_ID]: {[DIFFICULTY_10_MAN]: true, [DIFFICULTY_25_MAN]: true},
    [CLASSIC_DUNGEON_TK_ID]: {[DIFFICULTY_10_MAN]: false, [DIFFICULTY_25_MAN]: true},
};

const RETAIL_EXPANSION_ID        = 701;
const RETAIL_EXPANSION_SHORTNAME = 'retail_xpac';
const CLASSIC_EXPANSION_ID        = 702;
const CLASSIC_EXPANSION_SHORTNAME = 'classic_xpac';

const DUNGEON_EXPANSIONS = {
    [RETAIL_DUNGEON_ALPHA_ID]: RETAIL_EXPANSION_SHORTNAME,
    [RETAIL_DUNGEON_BETA_ID]: RETAIL_EXPANSION_SHORTNAME,
    [CLASSIC_DUNGEON_NONSPEEDRUN_ID]: CLASSIC_EXPANSION_SHORTNAME,
    [CLASSIC_DUNGEON_SSC_ID]: CLASSIC_EXPANSION_SHORTNAME,
    [CLASSIC_DUNGEON_TK_ID]: CLASSIC_EXPANSION_SHORTNAME,
};

const CURRENT_SEASON_ID = 501;
const KEY_LEVEL_MIN     = 2;
const KEY_LEVEL_MAX     = 20;

// season_dungeons/dungeons only ever contain the retail dungeons - classic has no seasons.
const CURRENT_SEASON_DUNGEON_IDS = [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID];

const DUNGEON_STARTS_BY_DUNGEON_ID = {
    [RETAIL_DUNGEON_ALPHA_ID]: [{id: 9011, text: 'Retail Alpha Start'}],
    [RETAIL_DUNGEON_BETA_ID]: [
        {id: 9021, text: 'Retail Beta Start A'},
        {id: 9022, text: 'Retail Beta Start B'},
        {id: 9023, text: 'Retail Beta Start C'},
    ],
};

const TEAM_ID = 9301;

// Default (non-awakened) affix group, and two awakened alternatives used by the multi-affix scenario.
const AFFIX_GROUP_DEFAULT_ID = 601;
const AFFIX_GROUP_ALT_A_ID   = 602;
const AFFIX_GROUP_ALT_B_ID   = 603;

const ATTRIBUTE_ALPHA_ID = 801;
const ATTRIBUTE_BETA_ID  = 802;

const FACTION_UNSPECIFIED_ID = 1;
const FACTION_HORDE_ID       = 2;
const FACTION_ALLIANCE_ID    = 3;
const CLASS_WARRIOR_ID       = 11;
const SPEC_FURY_ID           = 21;
const RACE_ORC_ID            = 31;

const PARTY_SIZE = 5;

/**
 * @param {number[]} dungeonIds - dungeons to render as `<option>`s, in this order.
 * @param {number} selectedId - the dungeon id that starts out `selected`.
 * @param {string} id
 * @param {string} name
 * @returns {string}
 */
function dungeonSelectHtml(dungeonIds, selectedId, id, name = 'dungeon_id') {
    const options = dungeonIds
        .map((dungeonId) => `<option value="${dungeonId}" ${dungeonId === selectedId ? 'selected' : ''}>Dungeon ${dungeonId}</option>`)
        .join('');

    return `<select id="${id}" name="${name}" class="form-control selectpicker" data-live-search="true">${options}</select>`;
}

/**
 * @param {boolean} hasTeams
 * @returns {string}
 */
function teamSelectHtml(hasTeams) {
    if (!hasTeams) {
        // common/team/select.blade.php only renders the <select> `@if(!$teams->isEmpty())` - with
        // no teams, `team_id` is entirely absent from the form (not just defaulted to -1).
        return '';
    }

    return `
        <div class="mb-3">
            <div class="row">
                <div class="col">
                    <select id="team_id_select" name="team_id" class="form-control selectpicker">
                        <option value="-1" selected>Select a team</option>
                        <option value="${TEAM_ID}">Sentinel Team</option>
                    </select>
                </div>
            </div>
        </div>`;
}

/**
 * @param {string} id
 * @param {string} dungeonSelectId
 * @returns {string}
 */
function dungeonDifficultySelectHtml(id, dungeonSelectId) {
    // create-mode: the container's inline style is always `display: none` on first render
    // (dungeondifficultyselect.js decides visibility during activate()); options start empty.
    return `
        <div id="${id}_container" class="mb-3" style="display: none;">
            <select id="${id}" name="dungeon_difficulty" class="form-control selectpicker"></select>
        </div>`;
}

/**
 * @param {string} id
 * @returns {string}
 */
function dungeonStartSelectHtml(id) {
    return `
        <div id="${id}_container" class="mb-3" style="display: none;">
            <select id="${id}" name="dungeon_start_map_icon_id" class="form-control selectpicker"></select>
        </div>`;
}

/**
 * @param {{id: number, seasonalIndex: number|null}[]} affixGroups
 * @param {boolean} withSeasonalIndexPreset
 * @returns {string}
 */
function affixesHtml(affixGroups, withSeasonalIndexPreset) {
    const affixOptions = affixGroups.map(({id}) => `<option value="${id}">${id}</option>`).join('');
    const affixRows = affixGroups.map(({id}) => `
        <div class="row affix_list_row expansion ${RETAIL_EXPANSION_SHORTNAME} season season-${CURRENT_SEASON_ID}" data-id="${id}">
            <div class="col col-md pe-0 affix_row">
                <div class="col-auto select_icon class_icon"></div>
            </div>
            <span class="col col-md-auto text-end ps-0"><span class="check" style="visibility: hidden;"><i class="fas fa-check"></i></span></span>
        </div>`).join('');

    const seasonalIndexSelect = withSeasonalIndexPreset ? `
        <div class="mb-3 ${RETAIL_EXPANSION_SHORTNAME} presets">
            <select id="seasonal_index" name="seasonal_index[]" class="form-control selectpicker">
                <option value="0">Preset 1</option>
                <option value="1">Preset 2</option>
                <option value="2">Preset 3</option>
            </select>
        </div>` : '';

    return `
        <div class="mb-3">
            <select id="route_select_affixes" name="route_select_affixes[]" multiple class="form-control affixselect d-none">${affixOptions}</select>
            <div id="route_select_affixes_list_custom" class="affix_list col-lg-12">${affixRows}</div>
        </div>
        ${seasonalIndexSelect}`;
}

/**
 * @returns {string}
 */
function attributesHtml() {
    return `
        <select multiple name="attributes[]" id="attributes" class="form-control selectpicker" size="2"
                data-selected-text-format="count > 1" data-none-selected-text="Select attributes" data-count-selected-text="{0} attributes">
            <optgroup label="Meta">
                <option value="${ATTRIBUTE_ALPHA_ID}">Attribute Alpha</option>
                <option value="${ATTRIBUTE_BETA_ID}">Attribute Beta</option>
            </optgroup>
        </select>`;
}

/**
 * @returns {string}
 */
function compositionHtml() {
    const factionOptions = `
        <option value="${FACTION_UNSPECIFIED_ID}" selected>Unspecified</option>
        <option value="${FACTION_HORDE_ID}">Horde</option>
        <option value="${FACTION_ALLIANCE_ID}">Alliance</option>`;

    let partyMembers = '';
    for (let i = 1; i <= PARTY_SIZE; i++) {
        partyMembers += `
            <select name="class[]" class="form-control selectpicker classselect" data-id="${i}"></select>
            <select data-live-search="true" name="specialization[]" class="form-control selectpicker specializationselect" data-id="${i}"></select>
            <select name="race[]" id="race_${i}" class="form-control selectpicker raceselect" data-id="${i}"></select>`;
    }

    return `
        <select name="faction_id" id="faction_id" class="form-control selectpicker">${factionOptions}</select>
        ${partyMembers}`;
}

/**
 * Builds the Create Route form DOM (or its guest/temporary variant) and inserts it into
 * `document.body`, mirroring the exact name/id/class attributes of the blade partials.
 *
 * @param {Object} config
 * @param {boolean} [config.temporary] - build createroutetemporary.blade.php's variant instead.
 * @param {'retail'|'classic'} [config.gameVersion] - retail has seasons (level/affixes), classic does not.
 * @param {boolean} [config.hasTeams] - whether the team select renders at all (ignored when temporary).
 * @param {number[]} [config.dungeonIds] - dungeon ids rendered as `<option>`s.
 * @param {number} [config.selectedDungeonId] - the dungeon id that starts out selected.
 * @param {{id: number, seasonalIndex: number|null}[]} [config.affixGroups] - affix rows to render (retail only).
 * @param {boolean} [config.withSeasonalIndexPreset] - render the awakened `#seasonal_index` preset select (retail only).
 * @returns {HTMLFormElement}
 */
function buildCreateRouteForm({
    temporary = false,
    gameVersion = 'retail',
    hasTeams = true,
    dungeonIds = [RETAIL_DUNGEON_ALPHA_ID, RETAIL_DUNGEON_BETA_ID],
    selectedDungeonId = dungeonIds[0],
    affixGroups = [{id: AFFIX_GROUP_DEFAULT_ID, seasonalIndex: null}],
    withSeasonalIndexPreset = false,
} = {}) {
    const hasSeasons        = gameVersion === 'retail';
    const dungeonSelectId   = temporary ? 'dungeon_id_select_temporary' : 'dungeon_id_select';
    const difficultySelectId = temporary ? 'dungeon_difficulty_select_temporary' : 'dungeon_difficulty_select';
    const startSelectId     = temporary ? 'dungeon_start_map_icon_id_temporary' : 'dungeon_start_map_icon_id';
    const levelInputId      = temporary ? 'temporary_dungeon_route_level' : 'dungeon_route_level';

    const levelHtml = hasSeasons ? `
        <input type="text" name="dungeon_route_level" id="${levelInputId}" class="form-control" style="display: none;" value="${KEY_LEVEL_MIN};${KEY_LEVEL_MAX}">` : '';

    let bodyHtml;
    if (temporary) {
        // createroutetemporary.blade.php: dungeon select, level, difficulty, start - no team,
        // no affixes, no attributes, no composition.
        bodyHtml = `
            <form id="createroutetemporary_form">
                <div class="container">
                    ${dungeonSelectHtml(dungeonIds, selectedDungeonId, dungeonSelectId)}
                    ${levelHtml}
                    ${dungeonDifficultySelectHtml(difficultySelectId, dungeonSelectId)}
                    ${dungeonStartSelectHtml(startSelectId)}
                    <input type="submit" value="Create">
                </div>
            </form>`;
    } else {
        // createroute.blade.php (create branch, !isset($dungeonroute)): dungeon, team, difficulty,
        // start, title, level+affixes (retail only), attributes+composition (always).
        bodyHtml = `
            <form id="createroute_form">
                <div class="container">
                    ${dungeonSelectHtml(dungeonIds, selectedDungeonId, dungeonSelectId)}
                    ${teamSelectHtml(hasTeams)}
                    ${dungeonDifficultySelectHtml(difficultySelectId, dungeonSelectId)}
                    ${dungeonStartSelectHtml(startSelectId)}
                    <input type="text" name="dungeon_route_title" id="dungeon_route_title" class="form-control" value="">
                    ${levelHtml}
                    ${hasSeasons ? affixesHtml(affixGroups, withSeasonalIndexPreset) : ''}
                    <div id="create_route_advanced_collapse">
                        ${attributesHtml()}
                        ${compositionHtml()}
                    </div>
                    <input type="submit" value="Create">
                </div>
            </form>`;
    }

    document.body.innerHTML = bodyHtml;

    return document.getElementById(temporary ? 'createroutetemporary_form' : 'createroute_form');
}

/**
 * Builds the `options` objects for every inline class involved in the Create Route form, mirroring
 * the shapes the blades pass via `@include('common.general.inline', ['options' => [...]])`. Sentinel
 * ids from this module are used wherever the blade would otherwise use DB-seeded ids.
 *
 * @param {Object} config
 * @param {boolean} [config.temporary]
 * @param {number[]} [config.defaultSelectedAffixes] - affix group ids pre-selected on load.
 * @returns {Object} keyed by inline class: createbase, dungeondifficultyselect, dungeonstartselect, affixes, composition
 */
function buildInlineOptions({temporary = false, defaultSelectedAffixes = [AFFIX_GROUP_DEFAULT_ID]} = {}) {
    const dungeonSelectId     = temporary ? 'dungeon_id_select_temporary' : 'dungeon_id_select';
    const difficultySelectId = temporary ? 'dungeon_difficulty_select_temporary' : 'dungeon_difficulty_select';
    const startSelectId       = temporary ? 'dungeon_start_map_icon_id_temporary' : 'dungeon_start_map_icon_id';
    const levelSelector       = temporary ? '#temporary_dungeon_route_level' : '#dungeon_route_level';

    const currentSeason = {
        id: CURRENT_SEASON_ID,
        key_level_min: KEY_LEVEL_MIN,
        key_level_max: KEY_LEVEL_MAX,
        expansion: {shortname: RETAIL_EXPANSION_SHORTNAME},
        season_dungeons: CURRENT_SEASON_DUNGEON_IDS.map((dungeonId) => ({dungeon_id: dungeonId})),
        dungeons: CURRENT_SEASON_DUNGEON_IDS.map((dungeonId) => ({id: dungeonId})),
        affix_groups: [
            {id: AFFIX_GROUP_DEFAULT_ID, expansion_id: RETAIL_EXPANSION_ID, seasonal_index: null},
            {id: AFFIX_GROUP_ALT_A_ID, expansion_id: RETAIL_EXPANSION_ID, seasonal_index: 0},
            {id: AFFIX_GROUP_ALT_B_ID, expansion_id: RETAIL_EXPANSION_ID, seasonal_index: 0},
        ],
    };

    return {
        createbase: {
            levelSelector,
            dungeonSelector: `#${dungeonSelectId}`,
            currentSeason,
            nextSeason: null,
            keyLevelMinDefault: KEY_LEVEL_MIN,
            keyLevelMaxDefault: KEY_LEVEL_MAX,
            levelFrom: KEY_LEVEL_MIN,
            levelTo: KEY_LEVEL_MAX,
        },
        dungeondifficultyselect: {
            dungeonSelectSelector: `#${dungeonSelectId}`,
            dungeonDifficultySelectSelector: `#${difficultySelectId}`,
            dungeonDifficultySelectContainerSelector: `#${difficultySelectId}_container`,
            speedrunDungeonIds: SPEEDRUN_DUNGEON_IDS,
            difficultyByDungeon: DIFFICULTY_BY_DUNGEON,
        },
        dungeonstartselect: {
            dungeonSelectId: `#${dungeonSelectId}`,
            dungeonStartSelectId: `#${startSelectId}`,
            dungeonStartContainerId: `#${startSelectId}_container`,
            dungeonStartsByDungeonId: DUNGEON_STARTS_BY_DUNGEON_ID,
            selectedDungeonStartId: null,
        },
        affixes: {
            dungeonroute: null,
            selectSelector: '#route_select_affixes',
            dungeonSelector: `#${dungeonSelectId}`,
            teemingSelector: '#teeming',
            modal: false,
            defaultSelected: defaultSelectedAffixes,
            allExpansions: {[RETAIL_EXPANSION_SHORTNAME]: RETAIL_EXPANSION_ID, [CLASSIC_EXPANSION_SHORTNAME]: CLASSIC_EXPANSION_ID},
            allAffixGroups: [
                {id: AFFIX_GROUP_DEFAULT_ID, expansion_id: RETAIL_EXPANSION_ID},
                {id: AFFIX_GROUP_ALT_A_ID, expansion_id: RETAIL_EXPANSION_ID},
                {id: AFFIX_GROUP_ALT_B_ID, expansion_id: RETAIL_EXPANSION_ID},
            ],
            dungeonExpansions: DUNGEON_EXPANSIONS,
            currentAffixes: {[RETAIL_EXPANSION_SHORTNAME]: null},
            currentSeason,
            nextSeason: null,
            seasonalIndexSelector: '#seasonal_index',
        },
        composition: {
            unspecifiedFactionId: FACTION_UNSPECIFIED_ID,
            factions: [
                {id: FACTION_UNSPECIFIED_ID, key: 'unspecified', name: 'factions.unspecified.name'},
                {id: FACTION_HORDE_ID, key: 'horde', name: 'factions.horde.name'},
                {id: FACTION_ALLIANCE_ID, key: 'alliance', name: 'factions.alliance.name'},
            ],
            classDetails: [
                {id: CLASS_WARRIOR_ID, key: 'warrior', name: 'classes.warrior.name', specializations: [{id: SPEC_FURY_ID}]},
            ],
            specializations: [
                {id: SPEC_FURY_ID, key: 'fury', name: 'specializations.fury.name', character_class_id: CLASS_WARRIOR_ID},
            ],
            races: [
                {id: RACE_ORC_ID, key: 'orc', name: 'races.orc.name', faction_id: FACTION_HORDE_ID, classes: [{id: CLASS_WARRIOR_ID}]},
            ],
        },
    };
}

/**
 * Instantiates and activates the real inline classes in production (blade) order, then performs the
 * single trailing `refreshSelectPickers()` call the layout does after all inline scripts have run
 * (see layouts/app.blade.php). Optional pieces (affixes/composition) are only activated when their
 * DOM is present, so the same helper works for both the full and temporary/guest forms.
 *
 * @param {HTMLFormElement} form
 * @param {Object} options - see buildInlineOptions()
 */
function activateCreateRouteForm(form, options) {
    // createroute.js/createroutetemporary.js reference CommonFormsCreatebase as a bare global (as in
    // the concatenated browser bundle), so it must be required and exposed before them.
    const {CommonFormsCreatebase} = require('../../custom/inline/common/forms/createbase');
    globalThis.CommonFormsCreatebase = CommonFormsCreatebase;

    const {
        CommonFormsCreateroute,
    } = require('../../custom/inline/common/forms/createroute');
    const {
        CommonFormsCreateroutetemporary,
    } = require('../../custom/inline/common/forms/createroutetemporary');
    const {
        CommonDungeonrouteCreateDungeondifficultyselect,
    } = require('../../custom/inline/common/dungeonroute/create/dungeondifficultyselect');
    const {
        CommonDungeonrouteCreateDungeonstartselect,
    } = require('../../custom/inline/common/dungeonroute/create/dungeonstartselect');
    const {
        CommonGroupAffixes,
    } = require('../../custom/inline/common/group/affixes');
    const {
        CommonGroupComposition,
    } = require('../../custom/inline/common/group/composition');
    // dungeondifficultyselect.js/dungeonstartselect.js/affixes.js/composition.js all call
    // refreshSelectPickers() as a bare global (as in the concatenated browser bundle).
    const {refreshSelectPickers} = require('../../selectpicker');
    globalThis.refreshSelectPickers = refreshSelectPickers;

    const isTemporary = form.id === 'createroutetemporary_form';

    const createbaseClass = isTemporary ? CommonFormsCreateroutetemporary : CommonFormsCreateroute;
    new createbaseClass('createbase', isTemporary ? 'common/forms/createroutetemporary' : 'common/forms/createroute', options.createbase).activate();

    new CommonDungeonrouteCreateDungeondifficultyselect('dungeondifficultyselect', 'common/dungeonroute/create/dungeondifficultyselect', options.dungeondifficultyselect).activate();
    new CommonDungeonrouteCreateDungeonstartselect('dungeonstartselect', 'common/dungeonroute/create/dungeonstartselect', options.dungeonstartselect).activate();

    if (form.querySelector('#route_select_affixes') !== null) {
        new CommonGroupAffixes('affixes', 'common/group/affixes', options.affixes).activate();
    }

    if (form.querySelector('#faction_id') !== null) {
        new CommonGroupComposition('composition', 'common/group/composition', options.composition).activate();
    }

    refreshSelectPickers();
}

module.exports = {
    // Dungeons
    RETAIL_DUNGEON_ALPHA_ID,
    RETAIL_DUNGEON_BETA_ID,
    CLASSIC_DUNGEON_NONSPEEDRUN_ID,
    CLASSIC_DUNGEON_SSC_ID,
    CLASSIC_DUNGEON_TK_ID,
    SPEEDRUN_DUNGEON_IDS,
    DIFFICULTY_10_MAN,
    DIFFICULTY_25_MAN,
    DIFFICULTY_BY_DUNGEON,
    // Expansions / season
    RETAIL_EXPANSION_ID,
    RETAIL_EXPANSION_SHORTNAME,
    CLASSIC_EXPANSION_ID,
    CLASSIC_EXPANSION_SHORTNAME,
    CURRENT_SEASON_ID,
    KEY_LEVEL_MIN,
    KEY_LEVEL_MAX,
    DUNGEON_STARTS_BY_DUNGEON_ID,
    // Team
    TEAM_ID,
    // Affixes
    AFFIX_GROUP_DEFAULT_ID,
    AFFIX_GROUP_ALT_A_ID,
    AFFIX_GROUP_ALT_B_ID,
    // Attributes
    ATTRIBUTE_ALPHA_ID,
    ATTRIBUTE_BETA_ID,
    // Composition
    FACTION_UNSPECIFIED_ID,
    FACTION_HORDE_ID,
    FACTION_ALLIANCE_ID,
    CLASS_WARRIOR_ID,
    SPEC_FURY_ID,
    RACE_ORC_ID,
    PARTY_SIZE,
    // Builders
    buildCreateRouteForm,
    buildInlineOptions,
    activateCreateRouteForm,
};
