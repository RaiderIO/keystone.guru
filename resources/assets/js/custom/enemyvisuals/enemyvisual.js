// Icon sizes
let _smallIcon = {iconSize: [48, 36]};
let _bigIcon = {iconSize: [48, 57]};

// Default icons
let _iconNames = [];

_iconNames['unset'] = _smallIcon;

let LeafletEnemyIcons = [];
let LeafletEnemyIconsKillZone = [];

// Build a library of icons to use
for (let key in _iconNames) {
    LeafletEnemyIcons[key] = new L.divIcon($.extend({className: key + '_enemy_icon'}, _iconNames[key]));
    LeafletEnemyIconsKillZone[key] = new L.divIcon($.extend({
        className: key + '_enemy_icon leaflet-edit-marker-selected ' +
            'killzone_enemy_icon_small'
    }, _iconNames[key]));
}


class EnemyVisual {
    constructor(map, enemy, layer) {
        console.assert(map instanceof DungeonMap, map, 'map was not a DungeonMap');
        console.assert(enemy instanceof Enemy, enemy, 'enemy was not an Enemy');

        this.map = map;
        this.enemy = enemy;
        this.layer = layer;
        this.divIcon = null;

        this.mainVisual = null;

        this.modifiers = [
            new EnemyVisualModifier(this, 0),
            new EnemyVisualModifierRaidMarker(this, 1),
            new EnemyVisualModifier(this, 2),
        ];

        // Default visual (after modifiers!)
        this.setMainVisual('aggressiveness');
    }

    /**
     * Constructs the structure for the visuals and re-fetches the main visual's and modifier's data to re-apply to
     * the interface.
     * @private
     */
    _buildVisual() {
        console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');
        // Prepare the template
        let iconHtml = $('#map_enemy_visual_template').html();
        // Remove template so our
        let template = handlebars.compile(iconHtml);

        let data = {};

        if (this.enemy.isKillZoneSelectable()) {
            data = {
                killzone_classes: 'leaflet-edit-marker-selected'
            };
        }

        data = $.extend(data, this.mainVisual._getTemplateData());
        for (let i = 0; i < this.modifiers.length; i++) {
            data = $.extend(data, this.modifiers[i]._getTemplateData());
        }

        // Build the status bar from the template
        iconHtml = template(data);

        // Create a new div icon (the entire structure)
        this.divIcon = new L.divIcon($.extend({html: iconHtml}, this.mainVisual.getSize()));

        // Set the structure as HTML for the layer
        this.layer.setIcon(this.divIcon);
    }

    // @TODO Listen to killzone selectable changed event
    refresh() {
        console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');
        this._buildVisual();
    }

    /**
     * Sets the main visual for this enemy.
     * @param name
     */
    setMainVisual(name) {
        if (this.mainVisual !== null) {
            // Let them clean up their mess
            this.mainVisual.cleanup();
        }

        // @TODO Create boss visual
        // Create a new visual based on the requested visual. With one exception, bosses are always shown with the
        // Aggressiveness filter. Otherwise they show up as things we don't want them to
        // @TODO Hard coded 3 = boss
        let isBoss = this.enemy.npc !== null && this.enemy.npc.classification_id === 3;
        if( isBoss ){
            name = 'aggressiveness';
        }

        switch (name) {
            case 'aggressiveness':
                this.mainVisual = new EnemyVisualMainAggressiveness(this);
                break;
            case 'enemy_forces':
                this.mainVisual = new EnemyVisualMainEnemyForces(this);
                break;
        }

        this._buildVisual();
    }

    /**
     *
     * @param name
     */
    setMainIcon(name) {
        console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');

        this.mainVisual.setIcon(name);
        this._buildVisual();
    }

    /**
     * Set a modifier's icon name by index. Pass null or an empty string to the name to unset.
     * @param index
     * @param name
     */
    setModifierIcon(index, name) {
        console.assert(index >= 0 && index <= 2, this, 'Index is out of bounds!');
        console.log(">> setModifierIcon", index, name);

        // Find the modifier of the index
        let modifier = this.modifiers[index];
        // Let it figure out its own icon by setting the name
        modifier.setIcon(name);
        this._buildVisual();
        console.log("OK setModifierIcon", index, name);
    }
}