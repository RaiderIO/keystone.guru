class MdtStringWarnings {
    constructor(warnings) {
        this.warnings = warnings;
    }

    /**
     * Renders the passed warnings to a target div element
     * @param $targetElement
     */
    render($targetElement) {
        let warningsTemplate = Handlebars.templates['import_string_warnings_template'];

        let warningsData = $.extend({}, getHandlebarsDefaultVariables(), {
            warnings: []
        });

        // construct the handlebars data
        for (let i = 0; i < this.warnings.length; i++) {
            let warning = this.warnings[i];

            warningsData.warnings.push({
                category: warning.category,
                message: warning.message,
                details: warning.data.hasOwnProperty('details') ? warning.data.details : ''
            });
        }

        // Assign the template data to the div
        $targetElement.html(warningsTemplate(warningsData));

        refreshTooltips();
    }
}
