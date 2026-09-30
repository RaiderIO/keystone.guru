// Renders the real compendium NPC template the way the compendium tables do - on top of
// getHandlebarsDefaultVariables() - so the template's `t` helper is asserted to resolve its
// translations from the visitor's locale without the caller passing them in.

global.$ = global.jQuery = require('jquery');
globalThis.isUserAdmin = false;
globalThis.csrfToken = 'test-csrf-token';

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');
const Lang = require('lang.js');

const npcTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(path.join(__dirname, '../handlebars/npc.handlebars'), 'utf8')
);

describe('getHandlebarsDefaultVariables', () => {
    const originalLang = globalThis.lang;
    const originalGetState = globalThis.getState;

    beforeAll(() => {
        globalThis.lang = new Lang({messages: {'de_DE_ai.js': {boss_label: 'Boss (de)'}}, locale: 'de_DE_ai'});
    });

    afterAll(() => {
        globalThis.lang = originalLang;
    });

    afterEach(() => {
        globalThis.getState = originalGetState;
    });

    it('getHandlebarsDefaultVariables_givenNpcTemplateForBoss_resolvesBossLabelInVisitorsLocale', () => {
        // Arrange
        const {getHandlebarsDefaultVariables} = require('./util');

        // Act
        document.body.innerHTML = npcTemplate($.extend({}, getHandlebarsDefaultVariables(), {
            compendium_url: 'https://keystone.guru/compendium/npc/1-boss',
            is_boss: true,
            boss_icon_url: 'https://assets.keystone.guru/skull.png',
            name: 'Some Boss',
        }));

        // Assert
        expect($('img[data-bs-toggle="tooltip"]').attr('title')).toBe('Boss (de)');
    });

    it('getHandlebarsDefaultVariables_givenCallerValue_callerValueWinsOverDefault', () => {
        // Arrange
        const {getHandlebarsDefaultVariables} = require('./util');

        // Act
        const data = $.extend({}, getHandlebarsDefaultVariables(), {csrf_token: 'Override'});

        // Assert
        expect(data.csrf_token).toBe('Override');
    });

    it('getHandlebarsDefaultVariables_givenAnyLocale_returnsNoTranslationKeys', () => {
        // Arrange
        const {getHandlebarsDefaultVariables} = require('./util');

        // Act
        const defaults = getHandlebarsDefaultVariables();

        // Assert
        expect(Object.keys(defaults).sort()).toEqual(['csrf_token', 'is_map_admin', 'is_user_admin']);
        expect(defaults.csrf_token).toBe('test-csrf-token');
        expect(defaults.is_user_admin).toBe(false);
    });

    it('getHandlebarsDefaultVariables_givenStateInitialisedAfterFirstCall_returnsCurrentIsMapAdmin', () => {
        // Arrange
        const {getHandlebarsDefaultVariables} = require('./util');
        globalThis.getState = () => false;
        const beforeState = getHandlebarsDefaultVariables();
        globalThis.getState = () => ({isMapAdmin: () => true});

        // Act
        const afterState = getHandlebarsDefaultVariables();

        // Assert
        expect(beforeState.is_map_admin).toBe(false);
        expect(afterState.is_map_admin).toBe(true);
    });

    it('getHandlebarsDefaultVariables_givenNonBoss_rendersNoBossIcon', () => {
        // Arrange
        const {getHandlebarsDefaultVariables} = require('./util');

        // Act
        document.body.innerHTML = npcTemplate($.extend({}, getHandlebarsDefaultVariables(), {
            compendium_url: 'https://keystone.guru/compendium/npc/2-trash',
            is_boss: false,
            name: 'Some Trash',
        }));

        // Assert
        expect($('img[data-bs-toggle="tooltip"]').length).toBe(0);
        expect($('a').text().trim()).toBe('Some Trash');
    });
});
