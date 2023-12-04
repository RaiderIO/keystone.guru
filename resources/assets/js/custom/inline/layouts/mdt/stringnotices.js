class MdtStringNotices {
    constructor(notices) {
        this.notices = notices;
    }

    /**
     * @return {String}
     */
    getHandlebarsTemplate() {
        console.error(`Implement getHandlebarsTemplate function!`);
    }

    /**
     * Renders the passed warnings to a target div element
     * @param $targetElement
     */
    render($targetElement) {
        let warningsTemplate = Handlebars.templates[this.getHandlebarsTemplate()];

        let noticesData = $.extend({}, getHandlebarsDefaultVariables(), {
            notices: []
        });

        // construct the handlebars data
        for (let i = 0; i < this.notices.length; i++) {
            let notice = this.notices[i];

            noticesData.notices.push({
                category: notice.category,
                message: notice.message,
                details: notice.data.hasOwnProperty('details') ? notice.data.details : ''
            });
        }

        // Assign the template data to the div
        $targetElement.html(warningsTemplate(noticesData));

        refreshTooltips();
    }
}
