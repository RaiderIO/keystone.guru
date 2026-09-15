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
