/**
 * Main visual icons only define an extra size.
 */

class EnemyVisualMain extends EnemyVisualIcon {
    constructor(enemyvisual) {
        super(enemyvisual);

        // Listen to changes in the NPC to update the icon and re-draw the visual
        this.enemyvisual.enemy.register('enemy:set_npc', this, this._refreshNpc.bind(this));
    }

    /**
     * Must be overriden by implementing classes
     * @protected
     */
    _refreshNpc(){

    }

    getSize() {
        return {};
    }

    cleanup() {
        super.cleanup();
        console.assert(this instanceof EnemyVisualMain, this, 'this is not an EnemyVisualMain!');

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
    }
}