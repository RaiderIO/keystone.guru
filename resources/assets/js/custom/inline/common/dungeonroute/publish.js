class CommonDungeonroutePublish extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

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

                let optionData = {
                    value: publishState,
                    text: lang.get(`messages.publish_state_title_${publishState}`),
                    selected: this.options.publishStateSelected === publishState ? 'selected' : false,
                };

                // If the user cannot activate an option for some reason, disable it but keep it visible
                if (!this.options.publishStatesAvailable.includes(publishState)) {
                    optionData.disabled = true;
                }
                let option = jQuery('<option>', optionData);


                let data = {
                    title: lang.get(`messages.publish_state_title_${publishState}`),
                    subtext: this.addNewlines(lang.get(`messages.publish_state_subtext_${publishState}`), 60),
                    fa_class: icons[publishState]
                };

                $select.append(
                    $(option).attr('data-content', template(data))
                );
            }
        }

        // When changed, trigger the change in the backend too
        $select.bind('change', function () {
            self._setPublished($(this).val());
        });
    }

    /**
     *
     * @param value string Must be one of the available published states
     * @private
     */
    _setPublished(value) {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/publishedState`,
            dataType: 'json',
            data: {
                published_state: value
            },
            success: function (json) {
                showSuccessNotification(lang.get('messages.route_published_state_changed'));
            }
        });
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