class LayoutsApp extends InlineCode {
    /**
     *
     */
    activate(){
        // Default error handler
        $.ajaxSetup({
            error: this._defaultAjaxErrorFn
        });

        // Fade out success messages. They're not too interesting
        $('#app_session_status_message').delay(7000).fadeOut(200);

        // Enable tooltips for all elements
        refreshTooltips();

        // Make sure selectpicker is enabled
        $('.selectpicker').selectpicker();

        $('#import_string_textarea').bind('paste', this._importStringPasted);

        if( this.options.guest ){
            this._newPassword('#register_password');
            this._newPassword('#modal-register_password');
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
        let $importString = $('#import_string_textarea');

        // Ugly, but needed since otherwise the field would be disabled prior to the value being actually assigned
        setTimeout(function () {
            // Can no longer edit it
            $importString.prop('disabled', true);
        }, 10);

        $.ajax({
            type: 'POST',
            url: '/ajax/mdt/details',
            dataType: 'json',
            data: {
                'import_string': typedEvent.originalEvent.clipboardData.getData('text')
            },
            beforeSend: function () {
                $('#import_string_loader').show();
            },
            complete: function () {
                $('#import_string_loader').hide();
            },
            success: function (responseData) {
                let detailsTemplate = Handlebars.templates['import_string_details_template'];

                let data = $.extend({
                    details: [
                        {key: lang.get('messages.mdt_faction'), value: responseData.faction},
                        {key: lang.get('messages.mdt_dungeon'), value: responseData.dungeon},
                        {key: lang.get('messages.mdt_affixes'), value: responseData.affixes.join('<br>')},
                        {key: lang.get('messages.mdt_pulls'), value: responseData.pulls},
                        {key: lang.get('messages.mdt_drawn_lines'), value: responseData.lines},
                        {key: lang.get('messages.mdt_notes'), value: responseData.notes},
                        {
                            key: lang.get('messages.mdt_enemy_forces'),
                            value: responseData.enemy_forces + '/' + responseData.enemy_forces_max
                        }
                    ]
                }, getHandlebarsDefaultVariables());

                // Build the preview from the template
                $('#import_string_details').html(detailsTemplate(data));

                // Inject the warnings, if there are any
                if (responseData.warnings.length > 0) {
                    let warningsTemplate = Handlebars.templates['import_string_warnings_template'];

                    let warningsData = $.extend({
                        warnings: []
                    }, getHandlebarsDefaultVariables());

                    // construct the handlebars data
                    for (let i = 0; i < responseData.warnings.length; i++) {
                        let warning = responseData.warnings[i];

                        warningsData.warnings.push({
                            category: warning.category,
                            message: warning.message,
                            details: warning.data.details
                        });
                    }

                    // Assign the template data to the div
                    $('#import_string_warnings').html(warningsTemplate(warningsData));
                }

                // Tooltips may be added above
                refreshTooltips();

                $('#import_string').val($importString.val());
                $('#mdt_import_modal input[type="submit"]').prop('disabled', false);
            }, error: function (xhr, textStatus, errorThrown) {
                $importString.removeProp('disabled');

                $('#import_string_details').html('');
                $('#import_string_warnings').html('');

                $('#mdt_import_modal input[type="submit"]').prop('disabled', true);
                self._defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    /**
     * The default function that should be called when an ajax request fails (error handler)
     **/
    _defaultAjaxErrorFn(xhr, textStatus, errorThrown) {
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

        addFixedFooterError(message + " (" + xhr.status + ")");
    }
}

/**
 * Refreshes fancy tooltips on all elements that request for them.
 */
function refreshTooltips() {
    if (!isMobile()) {
        $('[data-toggle="tooltip"]').tooltip('_fixTitle').tooltip();
    }
}

/**
 * Refreshes all select pickers on-screen
 **/
function refreshSelectPickers() {
    let $selectpicker = $('.selectpicker');
    $selectpicker.selectpicker('refresh');
    $selectpicker.selectpicker('render');
}

/**
 * Add a fixed message to a container with an arbitrary type.
 * @param containerSelector
 * @param type
 * @param message
 * @param durationMs
 * @param small
 * @private
 */
function _addFixedMessage(containerSelector, type, message, durationMs, small = false) {
    let template = Handlebars.templates['app_fixed_message_' + (small ? 'small_' : '') + 'template'];

    let handlebarsData = {
        type: type,
        message: message
    };

    let $message = $(template(handlebarsData));
    $(containerSelector).append($message);

    $message.delay(durationMs).fadeOut(200, function () {
        $(this).remove();
    });

    return $message;
}

/**
 * Add a fixed footer message with an arbitrary type.
 * @param type
 * @param message
 * @param durationMs
 * @param small
 * @private
 */
function _addFixedFooter(type, message, durationMs, small = false) {
    return _addFixedMessage('#fixed_footer_container', type, message, durationMs, small);
}

/**
 * Add a fixed header with an arbitrary type.
 * @param type
 * @param message
 * @param durationMs
 * @param small
 * @private
 */
function _addFixedHeader(type, message, durationMs, small = false) {
    return _addFixedMessage('#fixed_header_container', type, message, durationMs, small);
}

/**
 * Add a small fixed header which only wraps the content in a background.
 * @returns The created header element.
 **/
function addFixedHeaderSmall(message, durationMs = 5000) {
    return _addFixedHeader('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs, true);
}

/**
 * Add a small fixed footer which only wraps the content in a background.
 * @returns The created footer element.
 **/
function addFixedFooterSmall(message, durationMs = 5000) {
    return _addFixedFooter('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs, true);
}

/**
 * Adds a fixed info footer with a message and a duration to the bottom of the screen.
 * @returns The created footer element.
 */
function addFixedFooterInfo(message, durationMs = 5000) {
    return _addFixedFooter('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs);
}

/**
 * Adds a fixed success footer with a message and a duration to the bottom of the screen.
 * @returns The created footer element.
 */
function addFixedFooterSuccess(message, durationMs = 5000) {
    return _addFixedFooter('success', '<i class="fas fa-check-circle"></i> ' + message, durationMs);
}

/**
 * Adds a fixed warning footer with a message and a duration to the bottom of the screen.
 * @returns The created footer element.
 */
function addFixedFooterWarning(message, durationMs = 5000) {
    return _addFixedFooter('warning', '<i class="fas fa-exclamation-triangle"></i> ' + message, durationMs);
}

/**
 * Adds a fixed error footer with a message and a duration to the bottom of the screen.
 * @returns The created footer element.
 */
function addFixedFooterError(message, durationMs = 5000) {
    return _addFixedFooter('danger', '<i class="fas fa-times-circle"></i> ' + message, durationMs);
}