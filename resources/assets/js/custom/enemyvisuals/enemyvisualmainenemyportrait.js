class EnemyVisualMainEnemyPortrait extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'enemy_portrait';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return [];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemyPortrait, 'this is not an EnemyVisualMainEnemyPortrait!', this);

        let data = super._getTemplateData();

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_portrait text-white text-center';

        let enemyPortraitUrl = this.enemyvisual.enemy.npc === null ?
            `${this.enemyvisual.map.options.assetsBaseUrl}/images/enemyportraits/unknown.png` :
            `${this.enemyvisual.map.options.assetsBaseUrl}/${this.enemyvisual.enemy.npc.enemy_portrait_url}`;
        let template = Handlebars.templates['map_enemy_visual_enemy_portrait_template'];

        let isObsoleteOrOverpulled = this.enemyvisual.enemy.isObsolete() || this.enemyvisual.enemy.getOverpulledKillZoneId() !== null;
        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            id: this.enemyvisual.enemy.id,
            // Hide the portrait when obsolete or overpulled
            enemy_portrait_url: isObsoleteOrOverpulled ? null : enemyPortraitUrl,
            // Expensive calculation - only do it when we're going to use it
            width: isObsoleteOrOverpulled ? this._getTextWidth(3) : 0,
            obsolete: this.enemyvisual.enemy.isObsolete(),
            overpulled: this.enemyvisual.enemy.getOverpulledKillZoneId() !== null
        });

        data.main_visual_html = template(mainVisualData);

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    /**
     * @returns {string}
     */
    getName() {
        return 'EnemyVisualMainEnemyPortrait';
    }

    // No refreshSize() override on purpose. It used to run
    //   $(`#map_enemy_visual_${id}_enemy_portrait.obsolete, #map_enemy_visual_${id}_enemy_portrait.overpulled`)
    // on every refresh, which could never match: the template puts `.obsolete`/`.overpulled` on a
    // *child* of `#map_enemy_visual_${id}_enemy_portrait`, never on the element itself. Because
    // that selector qualifies an id with a class, jQuery could not take its getElementById fast
    // path either, so each call was two full-document querySelectorAll scans - times every enemy,
    // times every zoom step - to style nothing. On the Black Temple facade (552 enemies) that was
    // the single most expensive thing on the zoom path.
    //
    // The font size it meant to keep current is applied by the template at build time, and
    // shouldAlwaysRebuild() already returns true for obsolete/overpulled enemies, so those get a
    // full buildVisual() on every zoom level change and their text stays correctly sized.
}
