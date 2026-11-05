/**
 * Active while the mapper edits a box-selected group of enemies at once (see AdminEnemyMultiEdit). Draws the
 * selection border around exactly the selected enemies, and keeps their own edit popups from opening.
 */
class EnemyMultiEditEnemySelection extends EnemySelection {
    /**
     * @param map {DungeonMap}
     * @param enemies {Enemy[]}
     */
    constructor(map, enemies) {
        super(map, null);

        /** @type {Enemy[]} */
        this.enemies = enemies;
    }

    getName() {
        return 'EnemyMultiEditEnemySelection';
    }

    disablesTooltips() {
        return true;
    }

    drawsEnemyEditBorder() {
        return true;
    }

    shouldRebuildEnemyVisuals() {
        return true;
    }

    /**
     * Every enemy save is sent the moment the popup is submitted.
     * @returns {boolean}
     */
    shouldWarnUnsavedChanges() {
        return false;
    }

    /**
     * @param source {null}
     * @param enemyCandidate {Enemy}
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        return this.enemies.includes(enemyCandidate);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyMultiEditEnemySelection,
    };
}
