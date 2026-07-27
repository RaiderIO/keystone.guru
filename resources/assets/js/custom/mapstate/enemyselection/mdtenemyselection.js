class MDTEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'MDTEnemySelection';
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
     * Filters an enemy if it should be selected or not.
     * @param {Enemy} source
     * @param {Enemy} enemyCandidate
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(source instanceof Enemy, 'source is not an Enemy', source);
        console.assert(source.is_mdt, 'source not an MDT Enemy', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return !enemyCandidate.is_mdt && enemyCandidate.getMdtNpcId() === source.npc_id;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        return MDTEnemyIconSelected;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MDTEnemySelection,
    };
}
