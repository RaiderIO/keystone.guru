class CommonDungeonroutePublish extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        let $select = $(this.options.publishSelector);

        let icons = {
            'unpublished': 'fa-plane-arrival',
            'team': 'fa-users',
            'world': 'fa-globe',
            'world_with_link': 'fa-link',
        }

        for (let index in this.options.publishStates) {
            if (this.options.publishStates.hasOwnProperty(index)) {
                let publishState = this.options.publishStates[index];

                let template = Handlebars.templates['select_option_icon_subtext_template'];

                let option = jQuery('<option>', {
                    value: index,
                    text: lang.get(`messages.publish_state_title_${publishState}`),
                    selected: this.options.publishStateSelected === publishState
                });

                let data = {
                    title: lang.get(`messages.publish_state_title_${publishState}`),
                    subtext: this.addNewlines(lang.get(`messages.publish_state_subtext_${publishState}`), 35),
                    fa_class: icons[publishState]
                };

                $select.append(
                    $(option).attr('data-content', template(data))
                );
            }
        }
    }

    /**
     *
     * @param str
     * @param count
     * @returns {string}
     */
    addNewlines(str, count = 30) {
        let result = '';
        let charsAdded = 0;
        let words = str.split(' ');

        for (let i = 0; i < words.length; i++) {
            let word = words[i];
            if (charsAdded + word.length > count) {
                result += '<br>';
                charsAdded = 0;
            }

            result += word + ' ';

            // Add one for the space added
            charsAdded += (word.length + 1);
        }

        return result;
    }
}