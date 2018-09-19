$(function () {
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

    // Add icons to the faction dropdown
    $.each($('#faction_id option'), function (index, value) {
        let faction = _factions[index];
        let html = $('#template_faction_dropdown_icon_' + faction.name.toLowerCase()).html();
        $(value).data('content', html);
    });

    _fillSpecializations();
    _fillClasses();
    _fillRaces();

    _refreshSelectPicker();
});

function _refreshSelectPicker() {
    let $selectPicker = $('.selectpicker');

    $selectPicker.selectpicker('refresh');
    $selectPicker.selectpicker('render');
}

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
    console.log('>> _factionChanged', changeEvent);
    let newFactionId = parseInt($('#faction_id').val());

    // For each race select there is ..
    $.each($('.raceselect option'), function (index, value) {
        // Hide those options that aren't part of the faction
        let $option = $(value);

        let optionRaceId = parseInt($option.attr('value'));
        // If it's not the first entry (Race...), this race does not belong to the new faction, and the new faction is NOT unspecified..
        if (optionRaceId > 0 && _findRaceById(optionRaceId).faction_id !== newFactionId && newFactionId !== 1) {
            $option.hide();
        } else {
            $option.show();
        }
    });
    console.log('OK _factionChanged', changeEvent);
}

/**
 *
 * @param changeEvent
 * @private
 */
function _raceChanged(changeEvent) {
    console.log(changeEvent);

    // Changed by user
    if (changeEvent.originalEvent) {
        let $raceSelect = $(changeEvent.target);
        let newRaceId = parseInt($raceSelect.val());

        let $classSelect = $('.classselect').find("[data-id='" + $raceSelect.data('id') + "']");

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
        let race = _findRaceById(newRaceId);
        let raceFactionId = race.faction_id;

        let $faction = $('#faction_id');
        if (parseInt($faction.val()) !== raceFactionId) {
            // Trigger change event
            $faction.val(raceFactionId).change();
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
        // If it's not the first entry (Race...), and the candidate race is not part of the new class
        if (newClassId > 0 && optionRaceId > 0 && !_isClassPartOfRace(optionRaceId, newClassId)) {
            $option.hide();
        } else {
            $option.show();
        }
    });

    // Only update specs when updated by user, otherwise this event originated from changing a spec in the first place
    if (changeEvent.originalEvent) {
        let $specSelect = $('.specializationselect').find("[data-id='" + $classSelect.data('id') + "']");
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
    // Changed by user
    if (changeEvent.originalEvent) {
        let $specSelect = $(changeEvent.target);
        let newSpecId = parseInt($specSelect.val());

        let $classSelect = $('.classselect').find("[data-id='" + $specSelect.data('id') + "']");
        // For each race select there is ..
        $.each($classSelect.find('option'), function (index, value) {
            // Hide the classes that do not have this spec (all but one)
            let $option = $(value);

            let optionClassId = parseInt($option.attr('value'));
            // If it's not the first entry (Spec...), and the candidate race is not part of the new class
            if (newSpecId > 0 && optionClassId > 0 && !_isSpecPartOfClass(optionClassId, newSpecId)) {
                $option.hide();
            } else {
                $option.show();
                // Exception here, a spec only belongs to one class, force the change here.
                $classSelect.val(optionClassId);
                $classSelect.change();
            }
        });
    }
}

/**
 * Fills the relevant specialization select boxes with all available specializations
 * @private
 */
function _fillSpecializations() {
    $.each($('.specializationselect'), function (index, value) {
        let $specializationSelect = $(value);

        // Remove existing options
        $specializationSelect.find('option').remove();
        // Append default option
        $specializationSelect.append(jQuery('<option>', {
            value: '0', // Laravel can then accept values that haven't been set
            text: 'Specialization...'
        }));

        _addIconOptionToSelect($specializationSelect, _specializations);
    });
}

/**
 * Fills the relevant class select boxes with all available classes.
 * @private
 */
function _fillClasses() {
    $.each($('.classselect'), function (index, value) {
        let $classSelect = $(value);

        // Remove existing options
        $classSelect.find('option').remove();
        // Append default option
        $classSelect.append(jQuery('<option>', {
            value: '0', // Laravel can then accept values that haven't been set
            text: 'Class...'
        }));

        _addIconOptionToSelect($classSelect, _classDetails);
    });
}

/**
 * Fills the relevant race select boxes with all available races.
 * @private
 */
function _fillRaces() {
    $.each($('.raceselect'), function (index, value) {
        let $raceSelect = $(value);

        // Remove existing options
        $raceSelect.find('option').remove();
        // Append default option
        $raceSelect.append(jQuery('<option>', {
            value: '0', // Laravel can then accept values that haven't been set
            text: 'Race...'
        }));

        _addIconOptionToSelect($raceSelect, _races);
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
 * @private
 */
function _addIconOptionToSelect($select, dataCollection) {
    // Append the rest of the options
    for (let i = 0; i < dataCollection.length; i++) {
        let obj = dataCollection[i];

        let source = $('#composition_icon_option_template').html();
        let template = handlebars.compile(source);

        let option = jQuery('<option>', {
            value: obj.id,
            text: obj.name
        });

        let data = {
            url: obj.iconfile.icon_url,
            name: obj.name,
            name_lc: obj.name.replace(/ /g, '').toLowerCase()
        };

        $select.append(
            $(option).data('content', template(data))
        );
    }
}