// ---------------------------------------------------------------------------
// The "Get SimulationCraft string" button is not inside a <form>, so only guardedAjaxClick() stands
// between a double-click and two POST /ajax/{publicKey}/simulate requests (#4531).
//
// Uses a real jQuery (unlike the minimal `$` stub in test/setup.js) since the guard is keyed on
// jQuery's per-element data store and toggles the `disabled` property.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {guardedAjaxClick} = require('../../layouts/app');
globalThis.guardedAjaxClick = guardedAjaxClick;

globalThis.getState = () => ({getMapContext: () => ({getPublicKey: () => 'abc'})});
globalThis.Cookies = {get: () => null, set: () => undefined};

const {CommonDungeonrouteSimulate} = require('./simulate');

describe('CommonDungeonrouteSimulate', () => {
    let $button;
    let $raidBuffs;
    let $loader;
    let $result;
    let ajaxCalls;

    beforeEach(() => {
        // The minimal DOM _getData() and the ajax callbacks touch; every other simulate_* input may be
        // an empty jQuery set.
        $button = $('<button id="simulate_get_string"></button>').appendTo(document.body);
        $raidBuffs = $('<select id="simulate_raid_buffs" multiple></select>').appendTo(document.body);
        $loader = $('<div class="simulationcraft_export_loader_container"></div>').hide().appendTo(document.body);
        $result = $('<div class="simulationcraft_export_result_container"></div>').appendTo(document.body);
        ajaxCalls = [];

        $.ajax = (settings) => {
            ajaxCalls.push(settings);
            return {};
        };

        new CommonDungeonrouteSimulate('simulate', 'common.dungeonroute.simulate', {
            isThundering: false,
            keyLevelSelector: '#simulate_key_level',
            keyLevelMin: 2,
            keyLevelMax: 30,
        }).activate();
    });

    afterEach(() => {
        $button.remove();
        $raidBuffs.remove();
        $loader.remove();
        $result.remove();
    });

    test('_fetchSimulationCraftString_givenDoubleClick_firesExactlyOneRequest', () => {
        // Act
        $button.trigger('click');
        $button.trigger('click');

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(ajaxCalls[0].type).toBe('POST');
        expect(ajaxCalls[0].url).toBe('/ajax/abc/simulate');
    });

    test('_fetchSimulationCraftString_givenRequestInFlight_disablesTheButton', () => {
        // Act
        $button.trigger('click');

        // Assert
        expect($button.prop('disabled')).toBe(true);
    });

    test('_fetchSimulationCraftString_givenRequestCompletes_reEnablesTheButtonAndAllowsANewClick', () => {
        // Arrange
        $button.trigger('click');

        // Act
        ajaxCalls[0].complete({status: 500}, 'error');

        // Assert
        expect($button.prop('disabled')).toBe(false);
        $button.trigger('click');
        expect(ajaxCalls).toHaveLength(2);
    });

    test('_fetchSimulationCraftString_givenRequestCompletes_stillRunsTheOriginalComplete', () => {
        // Arrange
        $button.trigger('click');
        ajaxCalls[0].beforeSend();
        expect($loader.css('display')).not.toBe('none');
        expect($result.css('display')).toBe('none');

        // Act
        ajaxCalls[0].complete({status: 200}, 'success');

        // Assert
        expect($loader.css('display')).toBe('none');
        expect($result.css('display')).not.toBe('none');
    });
});

// ---------------------------------------------------------------------------
// The modal's inputs, the cookie that remembers them and the bloodlust-per-pull list. jsdom lays nothing out, so
// jQuery's `:hidden` holds for every element; an input that renders in the browser is given a client rect here.
// ---------------------------------------------------------------------------

globalThis._ = require('lodash');

describe('CommonDungeonrouteSimulate settings', () => {
    let ajaxCalls;
    let storedCookies;
    let killZones;

    /**
     * @param {HTMLElement} element
     * @returns {HTMLElement}
     */
    function rendered(element) {
        element.getClientRects = () => [{width: 1, height: 1}];

        return element;
    }

    /**
     * @param {Object} options
     * @returns {CommonDungeonrouteSimulate}
     */
    function activateSimulate(options = {}) {
        const simulate = new CommonDungeonrouteSimulate('simulate', 'common.dungeonroute.simulate', Object.assign({
            isThundering: false,
            keyLevelSelector: '#simulate_key_level',
            keyLevelMin: 2,
            keyLevelMax: 30,
        }, options));
        simulate.activate();

        return simulate;
    }

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="simulate_modal">
                <input id="simulate_key_level" value="10">
                <select id="simulate_affix"><option value="fortified">Fortified</option><option value="tyrannical">Tyrannical</option></select>
                <input id="simulate_thundering_clear_seconds" value="5">
                <select id="simulate_shrouded_bounty_type"><option value="none">None</option><option value="crit">Crit</option></select>
                <select id="simulate_raid_buffs" multiple>
                    <option value="1">Arcane Intellect</option>
                    <option value="2">Power Word: Fortitude</option>
                    <option value="4">Battle Shout</option>
                </select>
                <input id="simulate_hp_percent" value="100">
                <input id="simulate_ranged_pull_compensation_yards" value="20">
                <input id="simulate_use_mounts" type="checkbox" value="1">
                <select id="simulate_bloodlust_per_pull" multiple></select>
                <button id="simulate_get_string"></button>
                <textarea id="simulationcraft_export_result"></textarea>
                <div class="btn copy_simulationcraft_string_to_clipboard"></div>
            </div>`;
        ['#simulate_key_level', '#simulate_affix', '#simulate_hp_percent', '#simulate_use_mounts'].forEach(
            (selector) => rendered(document.querySelector(selector)),
        );

        ajaxCalls = [];
        $.ajax = (settings) => {
            ajaxCalls.push(settings);
            return {};
        };

        storedCookies = {};
        globalThis.Cookies = {
            get: (name) => storedCookies[name],
            set: vi.fn((name, value) => {
                storedCookies[name] = value;
            }),
        };
        globalThis.cookieDefaultAttributes = {path: '/'};

        killZones = {};
        globalThis.getKillZoneMapObjectGroup = () => ({objects: killZones});
        globalThis.BLOODLUST_SPELLS = [2825, 32182];
        globalThis.refreshSelectPickers = vi.fn();
        globalThis.copyToClipboard = vi.fn();
        $.fn.rangeSlider = vi.fn(function () {
            return this;
        });
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('_fetchSimulationCraftString_givenFilledInOptions_sendsThemWithTheRaidBuffsSummedIntoAMask', () => {
        // Arrange
        activateSimulate();
        $('#simulate_raid_buffs').val(['1', '4']);
        $('#simulate_use_mounts').prop('checked', true);
        $('#simulate_affix').val('tyrannical');
        $('#simulate_bloodlust_per_pull').append('<option value="11" selected>Pull 1</option>');

        // Act
        $('#simulate_get_string').trigger('click');

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(ajaxCalls[0].data).toEqual({
            simulate_bloodlust_per_pull: ['11'],
            key_level: '10',
            shrouded_bounty_type: 'none',
            affix: 'tyrannical',
            thundering_clear_seconds: '5',
            raid_buffs_mask: 5,
            hp_percent: '100',
            ranged_pull_compensation_yards: '20',
            use_mounts: 1,
        });
    });

    test('_fetchSimulationCraftString_givenNoRaidBuffsAndNoMounts_sendsAnEmptyMaskAndNoMounts', () => {
        // Arrange
        activateSimulate();

        // Act
        $('#simulate_get_string').trigger('click');

        // Assert
        expect(ajaxCalls[0].data.raid_buffs_mask).toBe(0);
        expect(ajaxCalls[0].data.use_mounts).toBe(0);
    });

    test('_fetchSimulationCraftString_givenSuccess_showsTheStringInTheResult', () => {
        // Arrange
        activateSimulate();
        $('#simulate_get_string').trigger('click');

        // Act
        ajaxCalls[0].success({string: 'fight_style=DungeonRoute'});

        // Assert
        expect($('#simulationcraft_export_result').val()).toBe('fight_style=DungeonRoute');
    });

    test('activate_givenCopyClicked_copiesTheResultString', () => {
        // Arrange
        activateSimulate();
        $('#simulationcraft_export_result').val('fight_style=DungeonRoute');

        // Act
        $('.copy_simulationcraft_string_to_clipboard').trigger('click');

        // Assert
        expect(copyToClipboard).toHaveBeenCalledTimes(1);
        expect(copyToClipboard.mock.calls[0][0]).toBe('fight_style=DungeonRoute');
        expect(copyToClipboard.mock.calls[0][1][0]).toBe(document.getElementById('simulationcraft_export_result'));
    });

    test('_saveSettings_givenAnOptionChanges_remembersEveryOptionInACookie', () => {
        // Arrange
        activateSimulate();
        $('#simulate_hp_percent').val('80');

        // Act
        $('#simulate_hp_percent').trigger('change');

        // Assert
        expect(Cookies.set).toHaveBeenCalledTimes(1);
        expect(Cookies.set.mock.calls[0][0]).toBe('simulate_modal_settings');
        expect(JSON.parse(Cookies.set.mock.calls[0][1])).toMatchObject({hp_percent: '80', key_level: '10', use_mounts: 0});
        expect(Cookies.set.mock.calls[0][2]).toEqual({path: '/'});
    });

    test('_loadSettings_givenRememberedSettings_restoresTheRenderedOptions', () => {
        // Arrange
        storedCookies.simulate_modal_settings = JSON.stringify({key_level: '15', affix: 'tyrannical', hp_percent: '60', use_mounts: 1});

        // Act
        activateSimulate();

        // Assert
        expect($('#simulate_key_level').val()).toBe('15');
        expect($('#simulate_affix').val()).toBe('tyrannical');
        expect($('#simulate_hp_percent').val()).toBe('60');
        expect($('#simulate_use_mounts').is(':checked')).toBe(true);
    });

    test('_loadSettings_givenMountsRememberedOff_unticksTheCheckbox', () => {
        // Arrange
        document.querySelector('#simulate_use_mounts').setAttribute('checked', 'checked');
        storedCookies.simulate_modal_settings = JSON.stringify({use_mounts: 0});

        // Act
        activateSimulate();

        // Assert
        expect($('#simulate_use_mounts').is(':checked')).toBe(false);
    });

    test('_loadSettings_givenRememberedSettingsForAnOptionThatIsNotRendered_leavesItAlone', () => {
        // Arrange - the shrouded bounty select is not rendered (no client rect); the hp input is
        storedCookies.simulate_modal_settings = JSON.stringify({shrouded_bounty_type: 'crit', hp_percent: '60'});

        // Act
        activateSimulate();

        // Assert
        expect($('#simulate_shrouded_bounty_type').val()).toBe('none');
        expect($('#simulate_hp_percent').val()).toBe('60');
    });

    test('_loadSettings_givenNoRememberedSettings_activatesWithoutThrowingAndKeepsTheDefaults', () => {
        // Arrange - js-cookie answers undefined for a cookie that was never set
        expect(Cookies.get('simulate_modal_settings')).toBeUndefined();

        // Act
        const act = () => activateSimulate();

        // Assert
        expect(act).not.toThrow();
        expect($('#simulate_key_level').val()).toBe('10');
        expect($('#simulate_use_mounts').is(':checked')).toBe(false);
    });

    test('_simulateModalOpened_givenPullsWithBloodlust_listsEveryPullInOrderAndTicksTheBloodlustOnes', () => {
        // Arrange
        killZones = {
            a: {id: 21, index: 2, spellIds: [2825]},
            b: {id: 11, index: 1, spellIds: []},
            c: {id: 31, index: 3, spellIds: [12345, 32182]},
        };
        activateSimulate();

        // Act
        $('#simulate_modal').trigger('show.bs.modal');

        // Assert
        const options = Array.from(document.querySelectorAll('#simulate_bloodlust_per_pull option'));
        expect(options.map((option) => option.value)).toEqual(['11', '21', '31']);
        expect(options.map((option) => option.text)).toEqual(['js.simulate_pull', 'js.simulate_pull', 'js.simulate_pull']);
        expect($('#simulate_bloodlust_per_pull').val()).toEqual(['21', '31']);
        expect(refreshSelectPickers).toHaveBeenCalledTimes(1);
    });

    test('_simulateModalOpened_givenPullsPickedBeforeReopening_keepsThosePicks', () => {
        // Arrange
        killZones = {
            a: {id: 11, index: 1, spellIds: [2825]},
            b: {id: 21, index: 2, spellIds: []},
        };
        activateSimulate();
        $('#simulate_modal').trigger('show.bs.modal');
        $('#simulate_bloodlust_per_pull').val(['21']);

        // Act
        $('#simulate_modal').trigger('show.bs.modal');

        // Assert
        expect($('#simulate_bloodlust_per_pull').val()).toEqual(['21']);
    });

    test('_simulateModalOpened_givenEveryPickedPullDeletedBeforeReopening_ticksTheBloodlustPulls', () => {
        // Arrange
        killZones = {
            a: {id: 11, index: 1, spellIds: [2825]},
            b: {id: 21, index: 2, spellIds: []},
        };
        activateSimulate();
        $('#simulate_modal').trigger('show.bs.modal');
        $('#simulate_bloodlust_per_pull').val(['21']);
        killZones = {a: {id: 11, index: 1, spellIds: [2825]}};

        // Act
        $('#simulate_modal').trigger('show.bs.modal');

        // Assert
        expect($('#simulate_bloodlust_per_pull').val()).toEqual(['11']);
    });

    test('_simulateModalOpened_givenTheFirstOpen_turnsTheNumericOptionsIntoSliders', () => {
        // Arrange
        activateSimulate();

        // Act
        $('#simulate_modal').trigger('show.bs.modal');

        // Assert
        expect($.fn.rangeSlider.mock.contexts.map((context) => context.attr('id'))).toEqual([
            'simulate_key_level',
            'simulate_hp_percent',
            'simulate_ranged_pull_compensation_yards',
        ]);
        expect($.fn.rangeSlider.mock.calls[0][0]).toMatchObject({type: 'single', min: 2, max: 30});
    });

    test('_simulateModalOpened_givenAThunderingRoute_alsoTurnsTheClearSecondsIntoASlider', () => {
        // Arrange
        activateSimulate({isThundering: true});

        // Act
        $('#simulate_modal').trigger('show.bs.modal');

        // Assert
        expect($.fn.rangeSlider.mock.contexts.map((context) => context.attr('id'))).toContain('simulate_thundering_clear_seconds');
        expect($.fn.rangeSlider).toHaveBeenCalledTimes(4);
    });

    test('_simulateModalOpened_givenASecondOpen_rebuildsThePullListButNotTheSliders', () => {
        // Arrange
        killZones = {a: {id: 11, index: 1, spellIds: []}};
        activateSimulate();
        $('#simulate_modal').trigger('show.bs.modal');
        killZones = {a: {id: 11, index: 1, spellIds: []}, b: {id: 21, index: 2, spellIds: []}};

        // Act
        $('#simulate_modal').trigger('show.bs.modal');

        // Assert
        expect($.fn.rangeSlider).toHaveBeenCalledTimes(3);
        expect($('#simulate_bloodlust_per_pull option')).toHaveLength(2);
    });
});
