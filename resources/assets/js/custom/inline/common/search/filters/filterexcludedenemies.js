class SearchFilterExcludedEnemies extends SearchFilter {
    constructor(onChange) {
        super(null, onChange);

        /** @type {EnemyMapObjectGroup} */
        this.enemyMapObjectGroup = null;
    }

    activate() {
        super.activate();

        for (let index in this.enemyMapObjectGroup.objects) {
            let enemy = this.enemyMapObjectGroup.objects[index];

            enemy.register('obsolete:changed', this, this._obsoleteChanged.bind(this));
        }
    }

    _obsoleteChanged() {
        this.onChange();
    }

    getValue() {
        let obsoleteEnemies = [];

        for (let index in this.enemyMapObjectGroup.objects) {
            /** @type {Enemy} */
            let enemy = this.enemyMapObjectGroup.objects[index];

            if (enemy.isObsolete()) {
                obsoleteEnemies.push(`${enemy.npc_id};${enemy.mdt_id}`);
            }
        }

        return obsoleteEnemies.length === 0 ? null : obsoleteEnemies;
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_excluded_enemies_header').replace(':value', this.getValue()?.length ?? 0);
    }
}
