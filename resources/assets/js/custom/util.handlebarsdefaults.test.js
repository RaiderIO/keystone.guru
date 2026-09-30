// Renders the real compendium NPC template the way the compendium tables do - on top of
// getHandlebarsDefaultVariables() - so the template's translation placeholders are asserted to resolve
// from the visitor's locale without the caller passing them in.

global.$ = global.jQuery = require('jquery');
globalThis.isUserAdmin = false;
globalThis.csrfToken = 'test-csrf-token';

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

const npcTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(path.join(__dirname, '../handlebars/npc.handlebars'), 'utf8')
);

describe('getHandlebarsDefaultVariables', () => {
    const originalLang = globalThis.lang;

    beforeAll(() => {
        globalThis.lang = {
            getLocale: () => 'de_DE_ai',
            get: (key) => key,
            messages: {'de_DE_ai.js': {boss_label: 'Boss (de)'}},
        };
    });

    afterAll(() => {
        globalThis.lang = originalLang;
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

    it('getHandlebarsDefaultVariables_givenCallerValue_callerValueWinsOverTranslation', () => {
        // Arrange
        const {getHandlebarsDefaultVariables} = require('./util');

        // Act
        document.body.innerHTML = npcTemplate($.extend({}, getHandlebarsDefaultVariables(), {
            compendium_url: 'https://keystone.guru/compendium/npc/1-boss',
            is_boss: true,
            boss_icon_url: 'https://assets.keystone.guru/skull.png',
            boss_label: 'Override',
            name: 'Some Boss',
        }));

        // Assert
        expect($('img[data-bs-toggle="tooltip"]').attr('title')).toBe('Override');
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
