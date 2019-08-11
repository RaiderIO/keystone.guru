class EnemyVisualModifier extends EnemyVisualIcon {
    constructor(enemyvisual, index) {
        super(enemyvisual);
        this.index = index;
        this.enemyvisual.register('enemyvisual:builtvisual', this, this._visualBuilt.bind(this));
    }

    /**
     * Checks if this modifier is visible. May be overridden by implementing classes.
     * @returns {boolean}
     * @private
     */
    _isVisible() {
        return this.enemyvisual.layer._icon instanceof Element && this.iconName !== '' && this.iconName !== null;
    }

    /**
     * Called whenever the visual has built its structure and we should manipulate it.
     * @private
     */
    _visualBuilt() {
        console.assert(this instanceof EnemyVisualModifier, 'this is not an EnemyVisualModifier!', this);
        // Only build our visual when we ourselves are visible
        if( this._isVisible() ){
            let element = this.enemyvisual.layer._icon;
            console.assert(element instanceof Element, 'element is not an Element! (Leaflet changed their internal structure?)', this);
            this.onVisualBuilt(element);
        }
    }

    /**
     * Called whenever the visual has been built, with the actual element as a parameter. Override this function in
     * child classes if needed.
     * @param element
     */
    onVisualBuilt(element) {
        let $element = $(element);
        let us = $element.find('.modifier_' + this.index);
        if (this._isVisible()) {
            us.show();
        } else {
            us.hide();
        }
    }

    cleanup() {
        super.cleanup();

        this.enemyvisual.unregister('enemyvisual:builtvisual', this);
    }
}