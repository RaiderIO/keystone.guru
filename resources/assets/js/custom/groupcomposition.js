$(function () {
    $('#faction_id').bind('change', function (event) {
        _factionChanged(event);

        _refreshSelectPicker();
    });
    $('.raceselect').bind('change', function (event) {
        _raceChanged(event);

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
 * Triggered whenever the user has changed the faction.
 *
 * @param event
 * @private
 */
function _factionChanged(event) {
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
}

/**
 *
 * @param event
 * @private
 */
function _raceChanged(event) {
    console.log(event);

    // Changed by user
    if( event.originalEvent ){
        let $raceSelect = $(event.target);
        let newRaceId = parseInt($raceSelect.val());

        let $classSelect = $('.classselect').find("[data-id='" + $raceSelect.data('id') + "']");

        console.log($raceSelect, $classSelect);
        // For each race select there is ..
        $.each($classSelect.find('option'), function (index, value) {
            // Hide those options that aren't part of the race
            let $option = $(value);

            let optionClassId = parseInt($option.attr('value'));
            // If it's not the first entry (Class...), this class does not belong to the new race
            if (optionClassId > 0 && !_isClassPartOfRace(newRaceId, parseInt(optionClassId))) {
                $option.hide();
            } else {
                $option.show();
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

        _addOptionToSelect($raceSelect, _races);
    });
}

/**
 * Adds a list of options to a select based on an object collection.
 * @param $select
 * @param dataCollection
 * @private
 */
function _addOptionToSelect($select, dataCollection) {

    // Append the rest of the options
    for (let i = 0; i < dataCollection.length; i++) {
        let obj = dataCollection[i];

        let option = jQuery('<option>', {
            value: obj.id,
            text: obj.name
        });

        $select.append($(option));
    }
}

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
            name: obj.name
        };

        $select.append(
            $(option).data('content', template(data))
        );
    }
}