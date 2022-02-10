class RowElement extends Signalable {

    constructor(killZonesSidebar, handlebarsTemplate) {
        super();

        this.killZonesSidebar = killZonesSidebar;
        this.handlebarsTemplate = handlebarsTemplate;
        // Shortcut
        this.map = this.killZonesSidebar.map;

        this.$visual = null;
    }

    /**
     *
     * @return {Object}
     * @protected
     */
    _getTemplateData() {
        return {};
    }

    /**
     *
     * @returns {null}
     */
    getVisual() {
        return this.$visual;
    }

    /**
     *
     */
    updateText() {

    }

    /**
     *
     */
    refresh() {

    }

    /**
     *
     * @param $targetContainer {jQuery}
     * @param $after {jQuery}
     */
    render($targetContainer, $after = null) {
        if ($after !== null) {
            console.assert($($after.parent()).attr('id') === $targetContainer.attr('id'), '$after parent must be the target container!', this);
        }

        // Build the handlebars template
        let template = Handlebars.templates[this.handlebarsTemplate];

        let data = $.extend({}, getHandlebarsDefaultVariables(), this._getTemplateData());

        // Render the element into the sidebar
        this.$visual = $(template(data));

        if ($after === null) {
            $targetContainer.append(this.$visual);
        } else {
            $after.after(this.$visual);
        }
    }

    /**
     *
     * @param $beforeElement
     */
    renderBefore($beforeElement) {
        // Build the handlebars template
        let template = Handlebars.templates[this.handlebarsTemplate];

        let data = $.extend({}, getHandlebarsDefaultVariables(), this._getTemplateData());

        $(template(data)).insertBefore($beforeElement);
    }

    remove() {

    }
}
