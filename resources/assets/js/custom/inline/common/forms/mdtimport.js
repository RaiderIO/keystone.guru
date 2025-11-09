/**
 * @typedef {Object} FormsMdtImportOptions
 * @property {string} temporaryRouteSelector
 * @property {string} mdtImportTeamIdSelector
 */

/**
 * @property {FormsMdtImportOptions} options
 */
class CommonFormsMdtimport extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        // When the MDT import modal is close, reset it
        $('#create_route_modal').on('hidden.bs.modal', this._resetMdtModal.bind(this));

        this.$importStringTextArea = $('.import_mdt_string_textarea').bind('paste', this._importStringPasted.bind(this));
        this.$root = this.$importStringTextArea.closest('.tab-pane');

        this.$loader = this.$root.find('.import_mdt_string_loader');
        this.$details = this.$root.find('.import_mdt_string_details');
        this.$warnings = this.$root.find('.mdt_string_warnings');
        this.$errors = this.$root.find('.mdt_string_errors');
        this.$importAsThisWeekContainer = this.$root.find('.import_as_this_week_container');
        this.$importAsThisWeek = this.$root.find('#import_as_this_week');
        this.$importString = this.$root.find('.import_string');
        this.$submitBtn = this.$root.find('input[type="submit"]');
        this.$resetBtn = this.$root.find('.import_mdt_string_reset_btn').unbind('click').bind('click', this._resetMdtModal.bind(this));

        let $temporaryRoute = $(this.options.temporaryRouteSelector);
        $temporaryRoute.bind('change', function () {
            let $mdtImportTeamIdSelect = $(this.options.mdtImportTeamIdSelector);

            if ($temporaryRoute.is(':checked')) {
                $mdtImportTeamIdSelect.attr('disabled', true);
            } else {
                $mdtImportTeamIdSelect.removeAttr('disabled');
            }

            refreshSelectPickers();
        });
    }


    /**
     * Called whenever the MDT import string has been pasted into the text area.
     **/
    _importStringPasted(typedEvent) {
        let self = this;
        // https://stackoverflow.com/questions/686995/catch-paste-input

        // Ugly, but needed since otherwise the field would be disabled prior to the value being actually assigned
        setTimeout(function () {
            // Can no longer edit it
            self.$importStringTextArea.prop('disabled', true);
        }, 10);

        $.ajax({
            type: 'POST',
            url: '/ajax/mdt/details',
            dataType: 'json',
            data: {
                'import_string': typedEvent.originalEvent.clipboardData.getData('text')
            },
            beforeSend: function () {
                self.$loader.show();
            },
            complete: function () {
                self.$loader.hide();
            },
            success: function (responseData) {
                let detailsTemplate = Handlebars.templates['import_string_details_template'];

                let details = [];
                if (responseData.hasOwnProperty('faction')) {
                    details.push({key: lang.get('messages.mdt_faction'), value: responseData.faction});
                }
                details.push({key: lang.get('messages.mdt_dungeon'), value: responseData.dungeon});
                details.push({key: lang.get('messages.mdt_affixes'), value: responseData.affixes.join('<br>')});
                details.push({key: lang.get('messages.mdt_pulls'), value: responseData.pulls});
                details.push({key: lang.get('messages.mdt_paths'), value: responseData.paths});
                details.push({key: lang.get('messages.mdt_drawn_lines'), value: responseData.lines});
                details.push({key: lang.get('messages.mdt_notes'), value: responseData.notes});
                details.push({
                    key: lang.get('messages.mdt_enemy_forces'),
                    value: `${responseData.enemy_forces}/${responseData.enemy_forces_max}`
                });


                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    details: details
                });

                // Build the preview from the template
                self.$details.html(detailsTemplate(data));

                // Inject the warnings, if there are any
                if (responseData.warnings.length > 0) {
                    (new MdtStringNoticesWarnings(responseData.warnings))
                        .render(self.$warnings);
                }
                if (responseData.errors.length > 0) {
                    (new MdtStringNoticesErrors(responseData.errors))
                        .render(self.$errors);
                }

                // If the route did not contain this week's affixes, offer to import it as such anyways
                self.$importAsThisWeek.prop('checked', !responseData.has_this_weeks_affix_group);
                self.$importAsThisWeekContainer.toggle(!responseData.has_this_weeks_affix_group);

                // Tooltips may be added above
                refreshTooltips();

                self.$importString.val(self.$importStringTextArea.val());
                self.$submitBtn.prop('disabled', responseData.errors.length > 0);

                self.$resetBtn.show();
            }, error: function (xhr, textStatus, errorThrown) {
                self._resetMdtModal();

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    /**
     * Reset the MDT modal to accept pastes again
     */
    _resetMdtModal() {
        this.$importStringTextArea.removeAttr('disabled').val('');

        this.$details.html('');
        this.$warnings.html('');
        this.$errors.html('');

        this.$submitBtn.prop('disabled', true);
    }
}
