class SearchFilterIncludedEnemies extends SearchFilter {
    constructor(onChange) {
        super(null, onChange);

        /** @type {EnemyMapObjectGroup} */
        this.enemyMapObjectGroup = null;
    }

    activate() {
        super.activate();

        for (let index in this.enemyMapObjectGroup.objects) {
            let enemy = this.enemyMapObjectGroup.objects[index];

            enemy.register('overpulled:changed', this, this._overpulledChanged.bind(this));
        }
    }

    _overpulledChanged() {
        console.assert(this instanceof SearchFilterIncludedEnemies, 'this is not a SearchFilterIncludedEnemies', this);

        this.onChange();
    }

    getValue() {
        let overpulledEnemies = [];

        for (let index in this.enemyMapObjectGroup.objects) {
            /** @type {Enemy} */
            let enemy = this.enemyMapObjectGroup.objects[index];

            if (enemy.getOverpulledKillZoneId() !== null) {
                overpulledEnemies.push(`${enemy.npc_id};${enemy.mdt_id}`);
            }
        }

        return overpulledEnemies.length === 0 ? null : overpulledEnemies;
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_included_enemies_header').replace(':value', this.getValue()?.length ?? 0);
    }
}
