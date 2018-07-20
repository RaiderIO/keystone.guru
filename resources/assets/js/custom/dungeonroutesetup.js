let _currentStage = 1;

let _stages = [
    {
        'id': 1,
        'saveCallback': function () {
            _selectedDungeonId = $("#dungeon").val();
        }
    }, {
        'id': 2,
        'saveCallback': function () {

        }
    },
    // {
    //     'id': 3,
    //     'initCallback': function () {
    //         // Get the data of the selected dungeon
    //         let dungeon = getDungeonDataById(_selectedDungeonId);
    //         // First floor, always
    //         setCurrentMapName(dungeon.key, 1);
    //         updateFloorSelection();
    //         // Refresh the map to reflect changes
    //         refreshLeafletMap();
    //     },
    //     'saveCallback': function () {
    //
    //     }
    // }
];
let _selectedDungeonId;

$(function () {
    $("#previous").bind('click', _previousStage);
    $("#next").bind('click', _nextStage);

    $("#faction").bind('change', _factionChanged);
    $(".raceselect").bind('change', _raceChanged);

    $(".selectpicker").selectpicker({
        showIcon: true
    });
});

function _factionChanged() {
    console.log(">> _factionChanged");

    let newFaction = $("#faction").val();
    let $raceSelect = $("select.raceselect");
    let $classSelect = $("select.classselect");

    // Remove all existing options
    $raceSelect.find('option').remove();
    $classSelect.find('option').remove();

    // Re-fill the races
    $raceSelect.append(jQuery('<option>', {
        value: -1,
        text: "Race..."
    }));

    for (let i = 0; i < _racesClasses.length; i++) {
        let raceClass = _racesClasses[i];
        if (raceClass.faction === newFaction) {
            $raceSelect.append(jQuery('<option>', {
                value: raceClass.id,
                text: raceClass.name
            }));
        }
    }

    $(".selectpicker").selectpicker('refresh');

    console.log("OK _factionChanged");
}

function _raceChanged() {
    console.log(">> _raceChanged");

    let $raceSelect = $(this);
    let raceId = parseInt($raceSelect.val());
    let $classSelect = $(".classselect").find("[data-id='" + $raceSelect.data('id') + "']");

    $classSelect.find('option').remove();

    // Find the raceclass for the class we've selected
    let raceClass = null;
    for (let i = 0; i < _racesClasses.length; i++) {
        if (_racesClasses[i].id === raceId) {
            raceClass = _racesClasses[i];
            break;
        }
    }

    console.assert(raceClass !== null, "RaceClass it not set (selected invalid class?)");

    // Match the raceClass to the classDetails
    for (let i = 0; i < raceClass.classes.length; i++) {
        let rClass = raceClass.classes[i];
        // Find the details
        for (let j = 0; j < _classDetails.length; j++) {
            let classDetail = _classDetails[j];
            // If found
            if (classDetail.id === rClass.id) {
                // Display it
                $classSelect.append(jQuery('<option>', {
                    value: classDetail.id, //zzz
                    text: classDetail.name,
                    'data-content': $("#template_dropdown_icon").html()
                        .replace('src=""', 'src="../../images/' + classDetail.iconfile.path + '"')
                        .replace('{text}', classDetail.name)
                }));
                break;
            }
        }
    }

    $('.selectpicker').selectpicker('refresh'); ///zzz
    $('.selectpicker').selectpicker('render'); ///zzz

    console.log("OK _raceChanged");
}

function _getStage(id) {
    for (let i = 0; i < _stages.length; i++) {
        if (_stages[i].id === id) {
            return _stages[i];
        }
    }
    return null;
}

function _previousStage() {
    if (_currentStage > 1) {
        _setStage(_currentStage - 1);
    }
}

function _nextStage() {
    if (_currentStage < _stages.length) {
        _setStage(_currentStage + 1);
    }
}

function _setStage(stage) {
    $("#stage-" + _currentStage).hide();
    $("#stage-" + stage).show();
    let currentStage = _getStage(_currentStage);
    if (currentStage.hasOwnProperty('saveCallback')) {
        currentStage.saveCallback();
    }

    let nextStage = _getStage(stage);
    if (nextStage.hasOwnProperty('initCallback')) {
        nextStage.initCallback();
    }

    _currentStage = stage;
    _handleButtonVisibility();
}

function _handleButtonVisibility() {
    if (_currentStage === 1) {
        $("#previous").addClass('hidden');
    } else {
        $("#previous").removeClass('hidden');
    }

    if (_currentStage === _stages.length) {
        $("#next").addClass('hidden');
        $("#finish").removeClass('hidden');
    } else {
        $("#next").removeClass('hidden');
    } //
}