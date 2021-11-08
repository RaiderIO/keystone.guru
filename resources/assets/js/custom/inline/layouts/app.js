class LayoutsApp extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        // Default error handler
        $.ajaxSetup({
            error: defaultAjaxErrorFn
        });

        // Fade out success messages. They're not too interesting
        $('#app_session_status_message').delay(7000).fadeOut(200);

        // Enable tooltips for all elements
        refreshTooltips();

        // Make sure selectpicker is enabled
        $('.selectpicker').selectpicker();

        // When the MDT import modal is close, reset it
        $('#create_route_modal').on('hidden.bs.modal', this._resetMdtModal.bind(this));

        this.$importStringTextArea = $('.import_mdt_string_textarea').bind('paste', this._importStringPasted.bind(this));
        this.$root = this.$importStringTextArea.closest('.modal');

        this.$loader = this.$root.find('.import_mdt_string_loader');
        this.$details = this.$root.find('.import_mdt_string_details');
        this.$warnings = this.$root.find('.mdt_string_warnings');
        this.$importString = this.$root.find('.import_string');
        this.$submitBtn = this.$root.find('input[type="submit"]');
        this.$resetBtn = this.$root.find('.import_mdt_string_reset_btn').bind('click', this._resetMdtModal.bind(this));


        if (this.options.guest) {
            this._newPassword('#register_password');
            this._newPassword('#modal-register_password');
        }

        $('.close,.close_alternative').bind('click', function () {
            let dismissId = $(this).data('alert-dismiss-id');
            // Cookie is now set to dismiss this alert permanently
            Cookies.set(`alert-dismiss-${dismissId}`, true, {expires: 30});
        });

        // When in a model-based layout with tabs, make sure the selected_modal_id actually moves the page to another when changed
        let $selectedModal = $('#selected_model_id');
        if ($selectedModal.length > 0) {
            $selectedModal.bind('change', function () {
                window.location.href = $("option:selected", this).data('url');
            });
        }

        // Theme switch button
        $('.theme_switch_btn').bind('click', function () {
            let theme = $(this).data('theme');
            let previousTheme = Cookies.get('theme');

            // Only when the theme has actually changed
            if (previousTheme !== theme) {
                // Switch images on the front page
                $(`.${previousTheme}_image`).hide();
                $(`.${theme}_image`).show();

                if (theme === 'lux') {
                    $('.navbar-dark').removeClass('navbar-dark').addClass('navbar-light');
                } else {
                    $('.navbar-light').removeClass('navbar-light').addClass('navbar-dark');
                }

                $('html').removeClass('superhero darkly lux').addClass(theme);
                // Regenerate parallax effects (switches images around)
                $('.mbr-parallax-background').jarallax('destroy').jarallax({speed: .6}).css('position', 'relative')

                Cookies.set('theme', theme);

                // Refresh the deme route
                let elem = document.getElementById('demo_routes_iframe');
                if (elem !== null) {
                    elem.contentWindow.location.reload();
                }
            }
        });
    }

    /**
     * Initiates a password checker on a 'enter your password' input.
     **/
    _newPassword(selector) {
        let $selector = $(selector);
        if ($selector.length > 0) {
            $selector.password({
                enterPass: '&nbsp;',
                shortPass: lang.get('messages.min_password_length'),
                badPass: lang.get('messages.weak'),
                goodPass: lang.get('messages.medium'),
                strongPass: lang.get('messages.strong'),
                containsUsername: lang.get('messages.contains_username'),
                showText: true, // shows the text tips
                animate: false, // whether or not to animate the progress bar on input blur/focus
                minimumLength: 8
            });
        }
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
                    value: responseData.enemy_forces + '/' + responseData.enemy_forces_max
                });


                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    details: details
                });

                // Build the preview from the template
                self.$details.html(detailsTemplate(data));

                // Inject the warnings, if there are any
                if (responseData.warnings.length > 0) {
                    (new MdtStringWarnings(responseData.warnings))
                        .render(self.$warnings);
                }

                // Tooltips may be added above
                refreshTooltips();

                self.$importString.val(self.$importStringTextArea.val());
                self.$submitBtn.prop('disabled', false);

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

        this.$submitBtn.prop('disabled', true);
    }
}

/**
 * The default function that should be called when an ajax request fails (error handler)
 **/
function defaultAjaxErrorFn(xhr, textStatus, errorThrown) {
    let message = lang.get('messages.ajax_error_default');

    switch (xhr.status) {
        case 403:
            message = lang.get('messages.ajax_error_403');
            break;
        case 404:
            message = lang.get('messages.ajax_error_404');
            break;
        case 419:
            message = lang.get('messages.ajax_error_419');
            break;
    }

    // If json was set
    if (typeof xhr.responseJSON === 'object') {
        // There were Laravel errors
        if (typeof xhr.responseJSON.errors === 'object') {
            let errors = xhr.responseJSON.errors;
            message = '';
            // Extract them and put them in the response string.
            for (let key in errors) {
                if (errors.hasOwnProperty(key)) {
                    message += errors[key] + ' ';
                }
            }
        } else if (typeof xhr.responseJSON.message === 'string') {
            if (xhr.responseJSON.message.length > 0) {
                message = xhr.responseJSON.message;
            }
        }
    }

    showErrorNotification(`${message} (${xhr.status})`);
}

/**
 *
 * @private
 */
function _hideTooltips() {
    $(this).tooltip('hide');
}

/**
 * Refreshes fancy tooltips on all elements that request for them.
 */
function refreshTooltips($element = null) {
    // console.warn('refreshing tooltips', $element);
    if (!isMobile()) {
        if ($element === null) {
            refreshTooltips($('[data-toggle="tooltip"]'));
            refreshTooltips($('[data-tooltip="tooltip"]'));
        } else {
            $('.tooltip').remove();
            $element.unbind('click', _hideTooltips.bind(this))
                .bind('click', _hideTooltips.bind(this))
                .tooltip('_fixTitle')
                .tooltip({trigger: 'manual'});
        }
    }
}

/**
 * Does the same as above, but then on a jQuery function
 */
$.fn.refreshTooltips = function () {
    return refreshTooltips($(this));
}

/**
 *
 * @param condition {Boolean}
 * @param closure {Function}
 * @returns {$}
 */
$.fn.if = function (condition, closure) {
    if (condition) {
        closure();
    }

    return this;
}

/**
 * Refreshes all select pickers on-screen
 **/
function refreshSelectPickers() {
    let $selectpicker = $('.selectpicker');
    $selectpicker.selectpicker('refresh');
    $selectpicker.selectpicker('render');
}

function _showNotification(opts) {
    new Noty($.extend({
        theme: 'keystoneguru',
        timeout: 4000
    }, opts)).show();
}

function _showConfirm(opts) {
    let n = new Noty($.extend({
        theme: 'keystoneguru',
        layout: 'center',
        modal: true
    }, opts));
    n.show();
}

/**
 *
 * @param text
 * @param yesCallback
 * @param noCallback
 * @param opts
 */
function showConfirmYesCancel(text, yesCallback, noCallback, opts = {}) {
    _showConfirm($.extend({
            type: 'confirm',
            text: text,
            buttons: [
                Noty.button(lang.get('messages.yes_label'), 'btn btn-success mr-1', function (n) {
                    if (typeof yesCallback === 'function') {
                        yesCallback();
                    }
                    n.close();
                }, {id: 'yes-button', 'data-status': 'ok'}),

                Noty.button(lang.get('messages.cancel_label'), 'btn btn-danger', function (n) {
                    if (typeof noCallback === 'function') {
                        noCallback();
                    }
                    n.close();
                })
            ]
        }, opts)
    );
}

/**
 *
 * @param text
 * @param doneCallback
 * @param opts
 */
function showConfirmFinished(text, doneCallback = null, opts = {}) {
    _showConfirm($.extend({
            type: 'confirm',
            text: text,
            buttons: [
                Noty.button(lang.get('messages.finished_label'), 'btn btn-success mr-1', function (n) {
                    if (typeof doneCallback === 'function') {
                        doneCallback();
                    }
                    n.close();
                }, {'data-status': 'ok'}),
            ]
        }, opts)
    );
}

/**
 * Shows a success notification message.
 * @param text The text to display.
 * @param opts
 */
function showSuccessNotification(text, opts = {}) {
    _showNotification($.extend({type: 'success', text: '<i class="fas fa-check-circle"></i> ' + text}, opts));
}

/**
 * Shows an info notification message.
 * @param text The text to display.
 * @param opts
 */
function showInfoNotification(text, opts = {}) {
    _showNotification($.extend({type: 'info', text: '<i class="fas fa-info-circle"></i> ' + text}, opts));
}

/**
 * Shows a warning notification message.
 * @param text The text to display.
 * @param opts
 */
function showWarningNotification(text, opts = {}) {
    _showNotification($.extend({type: 'warning', text: '<i class="fas fa-exclamation-triangle"></i> ' + text}, opts));
}

/**
 * Shows an error notification message.
 * @param text The text to display.
 * @param opts
 */
function showErrorNotification(text, opts = {}) {
    _showNotification($.extend({type: 'error', text: '<i class="fas fa-times-circle"></i> ' + text}, opts));
}
