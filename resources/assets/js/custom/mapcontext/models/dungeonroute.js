/**
 * @property publicKey {String}
 * @property teamId {Number}
 * @property seasonalIndex {Number}
 * @property pullGradient {String}
 * @property pullGradientApplyAlways {String}
 * @property faction {String}
 * @property enemyForces {Number}
 * @property levelMin {Number}
 * @property levelMax {Number}
 * @property dungeonDifficulty {String}
 * @property mappingVersionUpgradeUrl {String}
 * @property killZones {Array}
 * @property mapIcons {Array}
 * @property paths {Array}
 * @property brushlines {Array}
 * @property pridefulEnemies {Array}
 * @property enemyRaidMarkers {Array}
 * @property uniqueAffixes {Array}
 */
class DungeonRoute {
    constructor(dungeonRoute) {
        for (let propertyName in dungeonRoute) {
            this[propertyName] = dungeonRoute[propertyName];
        }
    }
}
