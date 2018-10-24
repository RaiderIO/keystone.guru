class EnemyVisualModifierInfestedVote extends EnemyVisualModifier {
    constructor(enemyvisual, index, yes) {
        super(enemyvisual, index);

        this.yes = yes;

        this.updateIcon();
        this.enemyvisual.enemy.register('enemy:infested_vote', this, this._userVotedInfested.bind(this));
    }

    _getValidIconNames() {
        return [
            'no',
            'yes',
            'no_disabled',
            'yes_disabled'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualModifierInfestedVote, this, 'this is not an EnemyVisualModifierInfestedVote!');

        let html = '';
        if (this.yes) {
            html = '<div title="Yes, this enemy is Infested this week">+</div>';
        } else {
            html = '<div title="No, this enemy is not Infested this week">-</div>';
        }

        let result = [];
        result['modifier_' + this.index + '_html'] = html;
        result['modifier_' + this.index + '_classes'] = 'text-center modifier_infested_vote ' +
            (this.iconName.indexOf('disabled') >= 0 ? 'modifier_infested_vote_disabled' : '');

        return result;
    }

    _userVotedInfested(voteEvent) {
        console.assert(this instanceof EnemyVisualModifierInfestedVote, this, 'this is not an EnemyVisualModifierInfestedVote!');

        // Determine what icon to display
        this.updateIcon();
        // Rebuild the visual
        this.setIcon(this.iconName);
    }

    onVisualBuilt(element) {
        super.onVisualBuilt(element);

        let self = this;

        let $btn = $(element).find('.modifier_' + this.index);
        // Disabled button cannot vote
        if (!$btn.hasClass('modifier_infested_vote_disabled')) {
            $btn.bind('click', function () {
                // Vote infested yes or no based on selection
                self.enemyvisual.enemy.voteInfested(self.yes);
            });
        }
    }

    updateIcon() {
        let enemy = this.enemyvisual.enemy;

        this.iconName = this.yes ? 'yes' : 'no';
        // Determine which icon to display
        if (enemy.infested_user_vote === 1 && this.yes) {
            this.iconName = 'yes_disabled';
        } else if (enemy.infested_user_vote === 0 && !this.yes) {
            this.iconName = 'no_disabled';
        }
    }

    cleanup() {
        super.cleanup();

        this.enemyvisual.enemy.unregister('enemy:infested_vote', this);
    }
}