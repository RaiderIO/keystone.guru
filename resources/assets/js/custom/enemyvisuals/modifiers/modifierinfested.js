class EnemyVisualModifierInfested extends EnemyVisualModifier {
    constructor(enemyvisual, index, voteMode) {
        super(enemyvisual, index);

        this.voteMode = voteMode;
        this.updateIcon();
        this.enemyvisual.enemy.register('enemy:infested_vote', this, this._userVotedInfested.bind(this));
    }

    _getValidIconNames() {
        return [
            '', // we are allowed to unset
            'infested',
            'infested_enabled',
            'infested_disabled'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualModifierInfested, this, 'this is not an EnemyVisualModifierInfested!');

        let classes = 'affix_icon_modifier ';
        let innerClass = '';
        if (this.iconName !== '') {
            classes += 'affix_icon_infested';
            if (this.iconName === 'infested_enabled') {
                innerClass += ' modifier_infested_enabled'
            } else if (this.iconName === 'infested_disabled') {
                innerClass += ' modifier_infested_disabled'
            }
        }

        let result = [];
        result['modifier_' + this.index + '_classes'] = classes;
        result['modifier_' + this.index + '_html'] = '<div class="' + innerClass + '">&nbsp;</div>';
        return result;
    }

    _userVotedInfested(voteEvent) {
        console.assert(this instanceof EnemyVisualModifierInfested, this, 'this is not an EnemyVisualModifierInfested!');

        // Determine what icon to display
        this.updateIcon();
        // Rebuild the visual
        this.setIcon(this.iconName);
    }

    updateIcon() {
        let enemy = this.enemyvisual.enemy;

        // console.log(enemy.id, enemy.is_infested, enemy);

        // @TODO Temp value 1
        // if (enemy.id === 1532) {
        //     console.log(enemy, enemy.infested_yes_votes, enemy.infested_no_votes,
        //         enemy.infested_yes_votes - enemy.infested_no_votes, enemy.is_infested);
        // }
        if (this.voteMode) {
            // Show if we're enabled or not
            if (enemy.is_infested) {
                this.iconName = 'infested_enabled';
            } else {
                this.iconName = 'infested_disabled';
            }
        } else if (enemy.is_infested) {
            this.iconName = 'infested';
        } else {
            this.iconName = '';
        }
    }

    cleanup() {
        super.cleanup();

        this.enemyvisual.enemy.unregister('enemy:infested_vote', this);
    }
}