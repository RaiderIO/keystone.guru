class EnemyVisualMainMDT extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'mdt';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return [];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainMDT, 'this is not an EnemyVisualMainMDT!', this);

        let data = super._getTemplateData();

        let text = this.enemyvisual.enemy.mdt_id;

        let size = this.enemyvisual.mainVisual.getSize();
        let width = size.iconSize[0];

        let margin = c.map.enemy.calculateMargin(width);
        width -= margin;

        // More characters to display..
        if (text >= 10) {
            width -= 7;
        }
        // Dangerous = less space
        else if (this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.dangerous) {
            width -= 6;
        }

        if (this.enemyvisual.enemy.enemy_id > 0) {
            data.main_visual_inner_classes += ' coupled';
        }
        data.main_visual_outer_classes += ' enemy_icon_npc_mdt text-black text-center';

        let template = Handlebars.templates['map_enemy_visual_enemy_mdt_template'];

        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            width: width,
            text: text
        });

        data.main_visual_html = template(mainVisualData);

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        this.setIcon(this.iconName);
    }

    // getSize() {
    //     console.assert(this instanceof EnemyVisualMainMDT, 'this is not an EnemyVisualMainMDT!', this);
    //
    //     return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    // }
}
