class SearchFilterExcludedEnemies extends SearchFilter {
    constructor(onChange) {
        super(null, onChange, {
            array: true
        });

        /** @type {EnemyMapObjectGroup} */
        this.enemyMapObjectGroup = null;

        this.onChangeTimeoutId = null;
    }

    activate() {
        super.activate();

        for (let index in this.enemyMapObjectGroup.objects) {
            let enemy = this.enemyMapObjectGroup.objects[index];

            enemy.register('obsolete:changed', this, this._obsoleteChanged.bind(this));
        }
    }

    _obsoleteChanged() {
        // Ensure that if this function is called multiple times in quick succession, only the last call triggers the onChange event
        if (this.onChangeTimeoutId !== null) {
            clearTimeout(this.onChangeTimeoutId);
        }

        let self = this;
        this.onChangeTimeoutId = setTimeout(() => {
            self.onChange();
        }, 100);
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

    /**
     *
     * @param value
     */
    setValue(value) {
        let enemies = value.split(',').map(item => item.split(';'));
        for (let index in enemies) {
            let enemy = this.enemyMapObjectGroup.getEnemyByNpcIdAndMdtId(parseInt(enemies[index][0]), parseInt(enemies[index][1]));
            if (enemy !== null) {
                enemy.setObsolete(true);
            }
        }
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_excluded_enemies_header').replace(':value', this.getValue()?.length ?? 0);
    }
}
