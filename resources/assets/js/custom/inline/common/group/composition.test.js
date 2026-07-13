// ---------------------------------------------------------------------------
// CommonGroupComposition is a global-script style class: it extends the bare global `InlineCode` and
// uses `$`/`jQuery`, `Handlebars` (stubbed - only used for `data-content`, never asserted on),
// `lang.get` (stubbed by test/setup.js to return the translation key) and `refreshSelectPickers`
// (stubbed here with a spy) at runtime.
//
// Kept deliberately small per the plan: fill-defaults, one selection case, one cross-filter case.
//
// Note: the change handlers only run their "changed by the user" branch when `changeEvent.
// originalEvent` is truthy, which jQuery's own `.trigger('change')` does NOT set - only a change
// event that reaches jQuery via a real/native dispatch does (this is how TomSelect's real sync
// reaches these handlers in production). So `dispatchNativeChange()` below uses the native DOM
// `dispatchEvent` API rather than jQuery's `.trigger()`.
//
// DOM is a single-party-member subset of common/group/composition.blade.php's markup (the full
// PARTY_SIZE=5 form from the shared fixture is overkill for these 3 scenarios); options extend
// buildInlineOptions()'s composition shape with one extra race (a different faction) so the
// cross-filter scenario has something to filter.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.Handlebars = {templates: {composition_icon_option_template: () => ''}};
globalThis.refreshSelectPickers = vi.fn();

const {CommonGroupComposition} = require('./composition');
const {
    buildInlineOptions,
    FACTION_UNSPECIFIED_ID,
    FACTION_HORDE_ID,
    FACTION_ALLIANCE_ID,
    CLASS_WARRIOR_ID,
    RACE_ORC_ID,
} = require('../../../../test/fixtures/createRouteForm');

const RACE_HUMAN_ID = 32;

/**
 * @param {Element} element
 */
function dispatchNativeChange(element) {
    element.dispatchEvent(new Event('change', {bubbles: true}));
}

/**
 * @returns {CommonGroupComposition}
 */
function buildAndActivate() {
    document.body.innerHTML = `
        <select name="faction_id" id="faction_id" class="form-control selectpicker"></select>
        <select name="class[]" class="form-control selectpicker classselect" data-id="1"></select>
        <select data-live-search="true" name="specialization[]" class="form-control selectpicker specializationselect" data-id="1"></select>
        <select name="race[]" id="race_1" class="form-control selectpicker raceselect" data-id="1"></select>`;

    const options = buildInlineOptions().composition;
    // Add a second race belonging to a different faction than RACE_ORC_ID (Horde), so the
    // faction cross-filter scenario has an incompatible option to hide.
    options.races = [...options.races, {id: RACE_HUMAN_ID, key: 'human', name: 'races.human.name', faction_id: FACTION_ALLIANCE_ID, classes: []}];

    const code = new CommonGroupComposition('composition', 'common/group/composition', options);
    code.activate();

    return code;
}

describe('CommonGroupComposition.activate', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('activate_givenNoSelectionMade_fillsClassSpecializationAndRaceSelectsWithAZeroValueDefaultOption', () => {
        // Arrange + Act
        buildAndActivate();

        // Assert: class[]/specialization[]/race[] each get a synthetic "unset" option valued "0"
        // (Laravel then accepts values that haven't been set) ahead of the real data-driven options.
        const classFirstOption = document.querySelector('.classselect[data-id="1"]').options[0];
        const specializationFirstOption = document.querySelector('.specializationselect[data-id="1"]').options[0];
        const raceFirstOption = document.querySelector('.raceselect[data-id="1"]').options[0];

        expect(classFirstOption.value).toBe('0');
        expect(classFirstOption.text).toBe('js.class_select');
        expect(specializationFirstOption.value).toBe('0');
        expect(specializationFirstOption.text).toBe('js.specialization_select');
        expect(raceFirstOption.value).toBe('0');
        expect(raceFirstOption.text).toBe('js.race_select');
    });

    it('activate_givenNoSelectionMade_fillsFactionSelectWithEveryFaction', () => {
        // Arrange + Act
        buildAndActivate();

        // Assert: unlike class/specialization/race, the faction select has no extra "0" default -
        // options.factions already includes an "Unspecified" entry.
        const factionOptions = Array.from(document.getElementById('faction_id').options);
        expect(factionOptions.map((option) => option.value)).toEqual([
            String(FACTION_UNSPECIFIED_ID),
            String(FACTION_HORDE_ID),
            String(FACTION_ALLIANCE_ID),
        ]);
    });

    it('activate_givenRaceSelectedByUser_syncsFactionSelectToThatRacesFaction', () => {
        // Arrange
        buildAndActivate();
        const raceSelect = document.querySelector('.raceselect[data-id="1"]');
        expect($('#faction_id').val()).toBe(String(FACTION_UNSPECIFIED_ID));

        // Act: pick Orc (Horde) as a real (native) user selection.
        raceSelect.value = String(RACE_ORC_ID);
        dispatchNativeChange(raceSelect);

        // Assert
        expect($('#faction_id').val()).toBe(String(FACTION_HORDE_ID));
    });

    it('activate_givenFactionSelectedByUser_hidesRaceOptionsBelongingToADifferentFaction', () => {
        // Arrange
        buildAndActivate();
        const factionSelect = document.getElementById('faction_id');
        const raceSelect = document.querySelector('.raceselect[data-id="1"]');

        // Act: pick Horde as a real (native) user selection.
        factionSelect.value = String(FACTION_HORDE_ID);
        dispatchNativeChange(factionSelect);

        // Assert: Orc (Horde) stays selectable, Human (Alliance) is filtered out.
        const orcOption = Array.from(raceSelect.options).find((option) => option.value === String(RACE_ORC_ID));
        const humanOption = Array.from(raceSelect.options).find((option) => option.value === String(RACE_HUMAN_ID));
        expect(orcOption.style.display).not.toBe('none');
        expect(humanOption.style.display).toBe('none');
    });
});
