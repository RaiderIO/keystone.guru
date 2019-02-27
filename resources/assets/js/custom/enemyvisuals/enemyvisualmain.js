/**
 * Main visual icons only define an extra size.
 */

class EnemyVisualMain extends EnemyVisualIcon {
    constructor(enemyvisual) {
        super(enemyvisual);

        let self = this;

        // Listen to changes in the NPC to update the icon and re-draw the visual
        this.enemyvisual.enemy.register('enemy:set_npc', this, this._refreshNpc.bind(this));

        this.enemyvisual.register('enemyvisual:builtvisual', this, function () {

            let id = self.enemyvisual.enemy.id;

            let template = Handlebars.templates['map_enemy_raid_marker_template'];

            let data = {
                id: id
            };

            let $container = $('#map_enemy_visual_' + id);
            $container.append(template(data));

            $container.find('ul').circleMenu({
                direction: 'full',
                trigger: 'click',
                circle_radius: 40,
                speed: 200,
                open: function () {
                    console.log('menu opened');
                },
                close: function () {
                    console.log('menu closed');
                },
                init: function () {
                    console.log('menu initialized');
                },
                select: function (evt, index) {
                    console.log(evt, index)
                }
            });
        });
    }

    /**
     * Must be overriden by implementing classes
     * @protected
     */
    _refreshNpc() {

    }

    getSize() {
        return {};
    }

    cleanup() {
        super.cleanup();
        console.assert(this instanceof EnemyVisualMain, this, 'this is not an EnemyVisualMain!');

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
        this.enemyvisual.unregister('enemyvisual:builtvisual', this);
    }
}