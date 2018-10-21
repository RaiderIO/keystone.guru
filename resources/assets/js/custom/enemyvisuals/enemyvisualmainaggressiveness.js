class EnemyVisualMainAggressiveness extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'unset';
    }

    _getValidIconNames() {
        return ['aggressive',
            'neutral',
            'unfriendly',
            'friendly',
            'unset',
            'flagged',
            'boss'];
    }

    _getTemplateData() {
        return {
            // Set the main icon
            main_visual_classes: (this.iconName + '_enemy_icon ') +
                // If we're in kill zone mode..
                (this.enemyvisual.enemy.isKillZoneSelectable() ?
                    // Adjust the size of the icon based on whether we're going big or small
                    'killzone_enemy_icon_' + (this.iconName === 'boss' ? 'big' : 'small')
                    : '')
        };
    }

    getSize() {
        return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    }
}