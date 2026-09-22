// A rare is not a boss: it keeps the icon of its NPC class, while boss and final boss share the boss icon.

global.Signalable = class Signalable {
};
global.EnemyVisual = class EnemyVisual {
};
global.EnemyVisualIcon = class EnemyVisualIcon extends Signalable {
};
global.EnemyVisualMain = class EnemyVisualMain extends EnemyVisualIcon {
};
global.NPC_CLASSIFICATION_ID_NORMAL = 1;
global.NPC_CLASSIFICATION_ID_ELITE = 2;
global.NPC_CLASSIFICATION_ID_BOSS = 3;
global.NPC_CLASSIFICATION_ID_FINAL_BOSS = 4;
global.NPC_CLASSIFICATION_ID_RARE = 5;

const {EnemyVisualMainEnemyClass} = require('./enemyvisualmainenemyclass');

function updateIconNameFor(classificationId) {
    const fakeThis = {
        iconName: 'melee',
        enemyvisual: {
            enemy: {npc: {id: 1, classification_id: classificationId, class: {key: 'caster'}}},
        },
    };

    EnemyVisualMainEnemyClass.prototype._updateIconName.call(fakeThis);

    return fakeThis.iconName;
}

test.each([
    ['boss', NPC_CLASSIFICATION_ID_BOSS],
    ['finalBoss', NPC_CLASSIFICATION_ID_FINAL_BOSS],
])('_updateIconName_given%sNpc_usesBossIcon', (label, classificationId) => {
    expect(updateIconNameFor(classificationId)).toBe('boss');
});

test.each([
    ['normal', NPC_CLASSIFICATION_ID_NORMAL],
    ['elite', NPC_CLASSIFICATION_ID_ELITE],
    ['rare', NPC_CLASSIFICATION_ID_RARE],
])('_updateIconName_given%sNpc_usesNpcClassIcon', (label, classificationId) => {
    expect(updateIconNameFor(classificationId)).toBe('caster');
});
