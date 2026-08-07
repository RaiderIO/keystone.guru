// FactionDisplayControls is a global-script class extending MapControl (another global-script
// class). Stub that at module-load time and exercise _buildFactionsData on a bare prototype
// instance (Object.create), same recipe as enemyforcesmanager.test.js.

global.MapControl = class MapControl {
    constructor(map) {
        this.map = map;
    }
};

// A non-en_US locale where the translated faction name does not match the faction key (in this
// project's real de_DE/es_ES/ru_RU lang files these translations are currently empty strings).
global.lang = {
    get: (key) => ({
        'factions.horde': 'Nicht Horde',
        'factions.alliance': 'Nicht Allianz',
    }[key] ?? ''),
};

const FactionDisplayControls = require('./factiondisplaycontrols');

function createControls() {
    return Object.create(FactionDisplayControls.prototype);
}

describe('FactionDisplayControls._buildFactionsData', () => {
    it('_buildFactionsData_givenFactions_usesFactionKeyNotTranslatedName', () => {
        const controls = createControls();

        const result = controls._buildFactionsData([
            {name: 'factions.horde', key: 'horde', icon_url: 'horde.png'},
            {name: 'factions.alliance', key: 'alliance', icon_url: 'alliance.png'},
        ]);

        // Before the fix, `key` was derived from `lang.get(faction.name).toLowerCase()` - here
        // that would yield 'nicht horde' / 'nicht allianz', which never matches the
        // `mapObject.faction` values ('horde'/'alliance') the click handler compares against.
        expect(result.map((faction) => faction.key)).toEqual(['horde', 'alliance']);
    });

    it('_buildFactionsData_givenTranslationDoesNotMatchKey_stillReturnsTheKey', () => {
        const controls = createControls();

        const result = controls._buildFactionsData([
            {name: 'factions.horde', key: 'horde', icon_url: 'horde.png'},
        ]);

        expect(result[0].key).toBe('horde');
        expect(result[0].name).toBe('Nicht Horde');
    });
});
