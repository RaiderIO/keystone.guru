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

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_mdt text-black text-center';
        data.main_visual_html = '<div style="font-size: ' + width + 'px; line-height: ' + width + 'px;">' + text + '</div>';

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    // getSize() {
    //     console.assert(this instanceof EnemyVisualMainMDT, 'this is not an EnemyVisualMainMDT!', this);
    //
    //     return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    // }
}