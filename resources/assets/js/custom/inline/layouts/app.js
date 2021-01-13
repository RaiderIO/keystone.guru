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

        $('.import_mdt_string_textarea').bind('paste', this._importStringPasted);

        if (this.options.guest) {
            this._newPassword('#register_password');
            this._newPassword('#modal-register_password');
        }

        $('.close').bind('click', function () {
            let dimissId = $(this).data('alert-dismiss-id');
            // Cookie is now set to dismiss this alert permanently
            Cookies.set('alert-dismiss-' + dimissId, true, {expires: 30});
        });

        // When in a model-based layout with tabs, make sure the selected_modal_id actually moves the page to another when changed
        let $selectedModal = $('#selected_model_id');
        if ($selectedModal.length > 0) {
            $selectedModal.bind('change', function () {
                let optionSelected = $("option:selected", this);

                window.location.href = optionSelected.data('url');
            });
        }
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
        let $importStringTextArea = $(this);
        let $root = $importStringTextArea.closest('.modal');
        console.log($root);

        let $loader = $root.find('.import_mdt_string_loader');
        let $details = $root.find('.import_mdt_string_details');
        let $importString = $root.find('.import_string');
        let $submitBtn = $root.find('input[type="submit"]');

        // Identify the type; sandbox or not.
        let $type = $root.find('.hidden_sandbox');
        $type.val($root.attr('id').includes('sandbox') ? 1 : 0);

        // Ugly, but needed since otherwise the field would be disabled prior to the value being actually assigned
        setTimeout(function () {
            // Can no longer edit it
            $importStringTextArea.prop('disabled', true);
        }, 10);

        $.ajax({
            type: 'POST',
            url: '/ajax/mdt/details',
            dataType: 'json',
            data: {
                'import_string': typedEvent.originalEvent.clipboardData.getData('text')
            },
            beforeSend: function () {
                $loader.show();
            },
            complete: function () {
                $loader.hide();
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
                $details.html(detailsTemplate(data));

                // Inject the warnings, if there are any
                if (responseData.warnings.length > 0) {
                    (new MdtStringWarnings(responseData.warnings))
                        .render($root.find('.mdt_string_warnings'));
                }

                // Tooltips may be added above
                refreshTooltips();

                $importString.val($importStringTextArea.val());
                $submitBtn.prop('disabled', false);
            }, error: function (xhr, textStatus, errorThrown) {
                $importStringTextArea.removeProp('disabled');

                $details.html('');
                $warnings.html('');

                $submitBtn.prop('disabled', true);
                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
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
 * Refreshes fancy tooltips on all elements that request for them.
 */
function refreshTooltips($element = null) {
    // console.warn('refreshing tooltips', $element);
    if (!isMobile()) {
        $('.tooltip').remove();
        if ($element === null) {
            $element = $('[data-toggle="tooltip"]');
        }
        $element.tooltip('_fixTitle').tooltip();
    }
}

/**
 * Does the same as above, but then on a jQuery function
 */
$.fn.refreshTooltips = function () {
    return refreshTooltips($(this));
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
        theme: 'bootstrap-v4',
        timeout: 4000
    }, opts)).show();
}

function _showConfirm(opts) {
    let n = new Noty($.extend({
        theme: 'bootstrap-v4',
        layout: 'center',
        modal: true
    }, opts));
    n.show();
}

function showConfirmYesCancel(text, yesCallback, noCallback, opts = {}) {
    _showConfirm($.extend({
            type: 'info',
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
 * Shows a success notification message.
 * @param text The text to display.
 */
function showSuccessNotification(text) {
    _showNotification({type: 'success', text: '<i class="fas fa-check-circle"></i> ' + text});
}

/**
 * Shows an info notification message.
 * @param text The text to display.
 */
function showInfoNotification(text) {
    _showNotification({type: 'info', text: '<i class="fas fa-info-circle"></i> ' + text});
}

/**
 * Shows a warning notification message.
 * @param text The text to display.
 */
function showWarningNotification(text) {
    _showNotification({type: 'warning', text: '<i class="fas fa-exclamation-triangle"></i> ' + text});
}

/**
 * Shows an error notification message.
 * @param text The text to display.
 */
function showErrorNotification(text) {
    _showNotification({type: 'error', text: '<i class="fas fa-times-circle"></i> ' + text});
}