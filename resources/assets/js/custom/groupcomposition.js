function initGroupComposition() {
    $('#faction_id').bind('change', function (changeEvent) {
        _factionChanged(changeEvent);

        _refreshSelectPicker();
    });

    $('.raceselect').bind('change', function (changeEvent) {
        _raceChanged(changeEvent);

        _refreshSelectPicker();
    });

    $('.classselect').bind('change', function (changeEvent) {
        _classChanged(changeEvent);

        _refreshSelectPicker();
    });

    $('.specializationselect').bind('change', function (changeEvent) {
        _specializationChanged(changeEvent);

        _refreshSelectPicker();
    });

    _fillFactions();
    _fillSpecializations();
    _fillClasses();
    _fillRaces();
}

/**
 * Refreshes the select picker to reflect the current state of the select boxes.
 * @private
 */
function _refreshSelectPicker() {
    let $selectPicker = $('.selectpicker');

    $selectPicker.selectpicker('refresh');
    $selectPicker.selectpicker('render');
}

/**
 * Finds a faction by its ID.
 * @param id
 * @returns {*}
 * @private
 */
function _findFactionById(id) {
    let faction = null;

    for (let i = 0; i < _factions.length; i++) {
        if (_factions[i].id === id) {
            faction = _factions[i];
            break;
        }
    }

    return faction;
}

/**
 * Finds a race by its ID.
 * @param id
 * @returns {*}
 * @private
 */
function _findRaceById(id) {
    let race = null;

    for (let i = 0; i < _races.length; i++) {
        if (_races[i].id === id) {
            race = _races[i];
            break;
        }
    }

    return race;
}

/**
 * Finds a class by its ID.
 * @param id
 * @returns {*}
 * @private
 */
function _findClassById(id) {
    let classDetail = null;

    for (let i = 0; i < _classDetails.length; i++) {
        if (_classDetails[i].id === id) {
            classDetail = _classDetails[i];
            break;
        }
    }

    return classDetail;
}

/**
 * Finds a spec by its ID.
 * @param id
 * @returns {*}
 * @private
 */
function _findSpecById(id) {
    let spec = null;

    for (let i = 0; i < _specializations.length; i++) {
        if (_specializations[i].id === id) {
            spec = _specializations[i];
            break;
        }
    }

    return spec;
}

/**
 * Checks if a class is part of a race.
 * @param raceId
 * @param classId
 * @returns {boolean}
 * @private
 */
function _isClassPartOfRace(raceId, classId) {
    let race = _findRaceById(raceId);

    let result = false;
    for (let i = 0; i < race.classes.length; i++) {
        let classDetail = race.classes[i];

        if (classDetail.id === classId) {
            result = true;
            break;
        }
    }

    return result;
}

/**
 * Checks if a spec is part of a class or not.
 * @param classId
 * @param specId
 * @returns {boolean}
 * @private
 */
function _isSpecPartOfClass(classId, specId) {
    let classDetail = _findClassById(classId);

    let result = false;
    for (let i = 0; i < classDetail.specializations.length; i++) {
        let specialization = classDetail.specializations[i];

        if (specialization.id === specId) {
            result = true;
            break;
        }
    }

    return result;
}

/**
 * Triggered whenever the user has changed the faction.
 *
 * @param changeEvent
 * @private
 */
function _factionChanged(changeEvent) {
    let newFactionId = parseInt($('#faction_id').val());

    // Keep a list of classselects
    let $classSelects = $('select.classselect');

    // For each race select there is ..
    $.each($('select.raceselect'), function (index, select) {
        let $select = $(select);
        let $classSelect = $($classSelects[index]);
        let currentClassId = parseInt($classSelect.val());

        // Adjust current selections in race if the faction changed, if faction was Alliance with a bunch
        // of Alliance races, faction switched to Horde, put all those selects to -1
        let currentRaceId = parseInt($select.val());
        let currentRace = _findRaceById(currentRaceId);
        // Check on null in case nothing was selected yet
        if (currentRace !== null && currentRace.faction_id !== newFactionId && newFactionId !== 1) {
            // select the 'Race...' option instead.
            $select.val(0);
        }

        $.each($select.find('option'), function (index, option) {
            // Hide those options that aren't part of the faction
            let $option = $(option);

            let optionRaceId = parseInt($option.attr('value'));
            // If not the Race... option
            if (optionRaceId > 0 &&
                // If the race candidate cannot support the selected class..
                ((currentClassId > 0 && !_isClassPartOfRace(optionRaceId, currentClassId)) ||
                    // Race does not belong to the new faction, or new faction was unspecified
                    _findRaceById(optionRaceId).faction_id !== newFactionId && newFactionId !== 1)) {
                $option.hide();
            } else {
                $option.show();
            }
        });
    });
}

/**
 *
 * @param changeEvent
 * @private
 */
function _raceChanged(changeEvent) {
    // Changed by user
    if (changeEvent.originalEvent) {
        let $raceSelect = $(changeEvent.target);
        let newRaceId = parseInt($raceSelect.val());

        let $classSelect = $('.classselect').find("[data-id='" + $raceSelect.data('id') + "']");

        if (newRaceId > 0) {
            // Check if the current class we've selected is still valid with this new race
            let currentClassId = parseInt($classSelect.val());

            if (currentClassId > 0 && currentClassId !== 0 && !_isClassPartOfRace(newRaceId, currentClassId)) {
                // select the 'Class...' option instead.
                $classSelect.val(0);
            }
        }

        // For each class select there is ..
        $.each($classSelect.find('option'), function (index, value) {
            // Hide those options that aren't part of the race
            let $option = $(value);

            let optionClassId = parseInt($option.attr('value'));
            // If it's not the first entry (Class...), this class does not belong to the new race
            if (newRaceId > 0 && optionClassId > 0 && !_isClassPartOfRace(newRaceId, parseInt(optionClassId))) {
                $option.hide();
            } else {
                $option.show();
            }
        });

        // Change faction to appropriate faction based on faction of race
        if (newRaceId > 0) {
            let race = _findRaceById(newRaceId);
            let raceFactionId = race.faction_id;

            let $faction = $('#faction_id');
            if (parseInt($faction.val()) !== raceFactionId) {
                // Trigger change event
                $faction.val(raceFactionId).change();
            }
        }
    }
}

/**
 *
 * @private
 */
function _classChanged(changeEvent) {
    // Changed by user
    let $classSelect = $(changeEvent.target);
    let newClassId = parseInt($classSelect.val());

    let $raceSelect = $('.raceselect').find("[data-id='" + $classSelect.data('id') + "']");
    // For each race select there is ..
    $.each($raceSelect.find('option'), function (index, value) {
        // Hide those options that aren't part of the faction
        let $option = $(value);

        let optionRaceId = parseInt($option.attr('value'));
        let optionRace = _findRaceById(optionRaceId);

        let currentFactionId = parseInt($('#faction_id').val());
        // If it's not the first entry (Race...), and the candidate race is not part of the new class, and if the faction
        if (optionRaceId > 0 && (newClassId > 0 && !_isClassPartOfRace(optionRaceId, newClassId) || (currentFactionId !== optionRace.faction_id && currentFactionId !== 0))) {
            $option.hide();
        } else {
            $option.show();
        }
    });

    // Only update specs when updated by user, otherwise this event originated from changing a spec in the first place
    if (changeEvent.originalEvent) {
        let $specSelect = $('.specializationselect').find("[data-id='" + $classSelect.data('id') + "']");

        if (newClassId > 0) {
            // Adjust current selections in spec if the class changed, if spec was set to Enhancement, and the class was changed
            // to something other than shaman, that is no longer a valid selection. Revert spec back to 'Specialization...' instead.
            let currentSpecId = parseInt($specSelect.val());

            // Check on null in case nothing was selected yet
            if (currentSpecId > 0 && newClassId !== 0 && !_isSpecPartOfClass(newClassId, currentSpecId)) {
                // select the 'Specialization...' option instead.
                $specSelect.val(0);
            }
        }

        // For each spec select there is ..
        $.each($specSelect.find('option'), function (index, value) {
            // Hide those options that aren't part of the class
            let $option = $(value);

            let optionSpecId = parseInt($option.attr('value'));
            // If it's not the first entry (Class...), this class does not belong to the new race
            if (newClassId > 0 && optionSpecId > 0 && !_isSpecPartOfClass(newClassId, parseInt(optionSpecId))) {
                $option.hide();
            } else {
                $option.show();
            }
        });
    }
}

/**
 *
 * @param changeEvent
 * @private
 */
function _specializationChanged(changeEvent) {
    let $specSelect = $(changeEvent.target);
    let newSpecId = parseInt($specSelect.val());

    let $classSelect = $('.classselect').find("[data-id='" + $specSelect.data('id') + "']");
    // For each race select there is ..
    $.each($classSelect.find('option'), function (index, value) {
        // Hide the classes that do not have this spec (all but one)
        let $option = $(value);

        let optionClassId = parseInt($option.attr('value'));
        // If it's not the first entry (Spec...), and the candidate race is not part of the new class
        if (newSpecId > 0 && (optionClassId === 0 || (optionClassId > 0 && !_isSpecPartOfClass(optionClassId, newSpecId)))) {
            $option.hide();
        } else {
            $option.show();
            // Don't trigger all this when the user has unselected a spec
            if (newSpecId > 0) {
                // Exception here, a spec only belongs to one class, force the change here.
                $classSelect.val(optionClassId);
                $classSelect.change();
            }
        }
    });
}

/**
 * Fills the factions dropdown with all factions.
 * @private
 */
function _fillFactions() {
    let $factionSelect = $('#faction_id');

    // Remove existing options
    $factionSelect.find('option').remove();

    _addIconOptionToSelect($factionSelect, _factions, 'faction_icon_');
}

/**
 * Fills the relevant specialization select boxes with all available specializations
 * @private
 */
function _fillSpecializations() {
    $.each($('select.specializationselect'), function (index, value) {
        let $specializationSelect = $(value);

        // Remove existing options
        $specializationSelect.find('option').remove();
        // Append default option
        $specializationSelect.append(jQuery('<option>', {
            value: '0', // Laravel can then accept values that haven't been set
            text: 'Specialization...'
        }));

        _addIconOptionToSelect($specializationSelect, _specializations, function (item) {
            let classDetails = _findClassById(item.character_class_id);
            return 'spec_icon_' + classDetails.name.replace(/ /g, '').toLowerCase() + '-' + item.name.replace(/ /g, '').toLowerCase();
        });
    });
}

/**
 * Fills the relevant class select boxes with all available classes.
 * @private
 */
function _fillClasses() {
    $.each($('select.classselect'), function (index, value) {
        let $classSelect = $(value);

        // Remove existing options
        $classSelect.find('option').remove();
        // Append default option
        $classSelect.append(jQuery('<option>', {
            value: '0', // Laravel can then accept values that haven't been set
            text: 'Class...'
        }));

        _addIconOptionToSelect($classSelect, _classDetails, 'class_icon_');
    });
}

/**
 * Fills the relevant race select boxes with all available races.
 * @private
 */
function _fillRaces() {
    $.each($('select.raceselect'), function (index, value) {
        let $raceSelect = $(value);

        // Remove existing options
        $raceSelect.find('option').remove();
        // Append default option
        $raceSelect.append(jQuery('<option>', {
            value: '0', // Laravel can then accept values that haven't been set
            text: 'Race...'
        }));

        _addIconOptionToSelect($raceSelect, _races, function (item) {
            let raceDetails = _findRaceById(item.id);
            return 'faction_icon_' + _findFactionById(raceDetails.faction_id).name.replace(/ /g, '').toLowerCase();
        });
    });
}

/**
 * Adds a list of options to a select based on an object collection.
 * @param $select
 * @param dataCollection
 * @private
 */
// function _addOptionToSelect($select, dataCollection) {
//
//     // Append the rest of the options
//     for (let i = 0; i < dataCollection.length; i++) {
//         let obj = dataCollection[i];
//
//         let option = jQuery('<option>', {
//             value: obj.id,
//             text: obj.name
//         });
//
//         $select.append($(option));
//     }
// }

/**
 * Adds a list of icon options to a select based on an object collection.
 * @param $select
 * @param dataCollection
 * @param cssPrefix
 * @private
 */
function _addIconOptionToSelect($select, dataCollection, cssPrefix = '') {
    // Append the rest of the options
    for (let i = 0; i < dataCollection.length; i++) {
        let obj = dataCollection[i];

        let source = $('#composition_icon_option_template').html();
        let template = handlebars.compile(source);

        let option = jQuery('<option>', {
            value: obj.id,
            text: obj.name
        });

        let currentCssPrefix = '';
        // Let user decide
        if (typeof cssPrefix === 'function') {
            currentCssPrefix = cssPrefix(obj);
        } else {
            // We make something up
            currentCssPrefix = cssPrefix + obj.name.replace(/ /g, '').toLowerCase();
        }

        let data = {
            name: obj.name,
            css_class: currentCssPrefix
        };

        $select.append(
            $(option).data('content', template(data))
        );
    }
}