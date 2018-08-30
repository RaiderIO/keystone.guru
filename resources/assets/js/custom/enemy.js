$(function () {
    L.Draw.Enemy = L.Draw.Marker.extend({
        statics: {
            TYPE: 'enemy'
        },
        options: {
            icon: LeafletNeutralEnemyIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Enemy.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletAggressiveEnemyIcon = new L.divIcon({className: 'aggressive_enemy_icon', iconSize: [12, 12]});
let LeafletNeutralEnemyIcon = new L.divIcon({className: 'neutral_enemy_icon', iconSize: [12, 12]});
let LeafletUnfriendlyEnemyIcon = new L.divIcon({className: 'unfriendly_enemy_icon', iconSize: [12, 12]});
let LeafletFriendlyEnemyIcon = new L.divIcon({className: 'friendly_enemy_icon', iconSize: [12, 12]});
let LeafletBossEnemyIcon = new L.divIcon({className: 'boss_enemy_icon', iconSize: [32, 32]});

let LeafletEnemyMarker = L.Marker.extend({
    options: {
        icon: LeafletAggressiveEnemyIcon
    }
});

class Enemy extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Enemy';
        // console.log(rand);
        // let hex = "#" + color.values[0].toString(16) + color.values[1].toString(16) + color.values[2].toString(16);

        this.setSynced(true);
    }

    getDifficultyColor(difficulty) {
        let palette = window.interpolate(c.map.enemy.colors);
        // let rand = Math.random();
        let color = palette(difficulty);
        this.setColors({
            saved: color,
            savedBorder: color,
            edited: color,
            editedBorder: color
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        super.onLayerInit();

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }

    setIcon(name){
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');

        switch(name){
            case 'aggressive':
                this.layer.setIcon(LeafletAggressiveEnemyIcon);

                break;
            case 'neutral':
                this.layer.setIcon(LeafletNeutralEnemyIcon);

                break;
            case 'unfriendly':
                this.layer.setIcon(LeafletUnfriendlyEnemyIcon);

                break;
            case 'friendly':
                this.layer.setIcon(LeafletFriendlyEnemyIcon);

                break;
            case 'boss':
                this.layer.setIcon(LeafletBossEnemyIcon);

                break;
        }
    }
}