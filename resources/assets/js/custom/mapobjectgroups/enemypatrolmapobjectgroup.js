class EnemyPatrolMapObjectGroup extends MapObjectGroup {
    constructor(map, name) {
        super(map, name);

        this.title = 'Hide/show enemy patrol routes';
        this.fa_class = 'fa-exchange-alt';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not an EnemyPatrolMapObjectGroup');

        // No AdminEnemyPatrol; this is managed by the enemy itself.
        return new EnemyPatrol(this.map, layer);
    }

    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof EnemyPatrolMapObjectGroup, this, 'this is not a EnemyPatrolMapObjectGroup');

        console.log('Not fetching enemy patrols from server; they\'re set by the enemy!');
    }
}