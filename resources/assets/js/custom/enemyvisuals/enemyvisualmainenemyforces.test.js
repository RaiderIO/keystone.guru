// Coverage for EnemyVisualMainEnemyForces#_getDisplayText (#4406): a speedrun-required NPC's
// enemy forces value is meaningless for that enemy, so the icon shows `1` instead, regardless of
// the selected number style. isSpeedrunRequiredNpc() itself (the shared check with the
// `required_npc` border class) is covered in enemyvisualmain.test.js; here it is stubbed directly
// on the fake `this`.

global.Signalable = class Signalable {
};
global.EnemyVisual = class EnemyVisual {
};
global.EnemyVisualIcon = class EnemyVisualIcon extends Signalable {
};
global.EnemyVisualMain = class EnemyVisualMain extends EnemyVisualIcon {
    isSpeedrunRequiredNpc() {
        return false;
    }
};

const {EnemyVisualMainEnemyForces} = require('./enemyvisualmainenemyforces');

global.NUMBER_STYLE_ENEMY_FORCES = 'enemy_forces';
global.getFormattedPercentage = (value, required) => `${Math.round((value / required) * 100)}%`;

function makeFakeThis({isSpeedrunRequiredNpc, numberStyle, enemyForces, enemyForcesRequired}) {
    global.getState = () => ({
        getMapNumberStyle: () => numberStyle,
    });

    return {
        isSpeedrunRequiredNpc: () => isSpeedrunRequiredNpc,
        enemyvisual: {
            enemy: {
                getEnemyForces: () => enemyForces,
            },
            map: {
                enemyForcesManager: {
                    getEnemyForcesRequired: () => enemyForcesRequired,
                },
            },
        },
    };
}

test('_getDisplayText_givenSpeedrunRequiredNpcAndRawNumberStyle_returnsOne', () => {
    const fakeThis = makeFakeThis({
        isSpeedrunRequiredNpc: true,
        numberStyle: global.NUMBER_STYLE_ENEMY_FORCES,
        enemyForces: 42,
        enemyForcesRequired: 100,
    });

    expect(EnemyVisualMainEnemyForces.prototype._getDisplayText.call(fakeThis)).toBe('1');
});

test('_getDisplayText_givenSpeedrunRequiredNpcAndPercentageNumberStyle_returnsOne', () => {
    const fakeThis = makeFakeThis({
        isSpeedrunRequiredNpc: true,
        numberStyle: 'percentage',
        enemyForces: 42,
        enemyForcesRequired: 100,
    });

    expect(EnemyVisualMainEnemyForces.prototype._getDisplayText.call(fakeThis)).toBe('1');
});

test('_getDisplayText_givenNotSpeedrunRequiredAndRawNumberStyle_returnsEnemyForces', () => {
    const fakeThis = makeFakeThis({
        isSpeedrunRequiredNpc: false,
        numberStyle: global.NUMBER_STYLE_ENEMY_FORCES,
        enemyForces: 42,
        enemyForcesRequired: 100,
    });

    expect(EnemyVisualMainEnemyForces.prototype._getDisplayText.call(fakeThis)).toBe('42');
});

test('_getDisplayText_givenNotSpeedrunRequiredAndPercentageNumberStyle_returnsFormattedPercentage', () => {
    const fakeThis = makeFakeThis({
        isSpeedrunRequiredNpc: false,
        numberStyle: 'percentage',
        enemyForces: 42,
        enemyForcesRequired: 100,
    });

    expect(EnemyVisualMainEnemyForces.prototype._getDisplayText.call(fakeThis)).toBe('42%');
});
