// The truesight column reaches the browser as whatever the database driver and JSON serialization
// produce (1, "1" or true); the icon must show for every truthy form, not only the integer 1.

global.EnemyVisualModifier = class EnemyVisualModifier {
    constructor(enemyvisual, index) {
        this.enemyvisual = enemyvisual;
        this.index = index;
    }
};

const {EnemyVisualModifierTruesight} = require('./modifiertruesight');

function makeEnemyVisual(npc) {
    return {enemy: {npc}};
}

test.each([
    ['anInteger', 1],
    ['aNumericString', '1'],
    ['aBoolean', true],
])('constructor_givenTruesightAs%s_showsTruesightIcon', (label, truesight) => {
    const modifier = new EnemyVisualModifierTruesight(makeEnemyVisual({truesight}), 2);

    expect(modifier.iconName).toBe('truesight');
});

test.each([
    ['anInteger', 0],
    ['aBoolean', false],
])('constructor_givenTruesightDisabledAs%s_showsNoIcon', (label, truesight) => {
    const modifier = new EnemyVisualModifierTruesight(makeEnemyVisual({truesight}), 2);

    expect(modifier.iconName).toBe('');
});

test('constructor_givenNoNpc_showsNoIcon', () => {
    const modifier = new EnemyVisualModifierTruesight(makeEnemyVisual(null), 2);

    expect(modifier.iconName).toBe('');
});
