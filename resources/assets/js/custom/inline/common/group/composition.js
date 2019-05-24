class CommonGroupComposition extends InlineCode {
    activate() {
        super.activate();

        let self = this;

        $('#faction_id').bind('change', function (changeEvent) {
            self._factionChanged(changeEvent);

            refreshSelectPickers();
        });

        $('.raceselect').bind('change', function (changeEvent) {
            self._raceChanged(changeEvent);

            refreshSelectPickers();
        });

        $('.classselect').bind('change', function (changeEvent) {
            self._classChanged(changeEvent);

            refreshSelectPickers();
        });

        $('.specializationselect').bind('change', function (changeEvent) {
            self._specializationChanged(changeEvent);

            refreshSelectPickers();
        });

        $('#reload_button').bind('click', function (e) {
            e.preventDefault();
            self._loadDungeonRouteDefaults();
        });

        this._fillFactions();
        this._fillSpecializations();
        this._fillClasses();
        this._fillRaces();
    }

    /**
     * Finds a faction by its ID.
     * @param id
     * @returns {*}
     * @private
     */
    _findFactionById(id) {
        let faction = null;

        for (let i = 0; i < this.options.factions.length; i++) {
            if (this.options.factions[i].id === id) {
                faction = this.options.factions[i];
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
    _findRaceById(id) {
        let race = null;

        for (let i = 0; i < this.options.races.length; i++) {
            if (this.options.races[i].id === id) {
                race = this.options.races[i];
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
    _findClassById(id) {
        let classDetail = null;

        for (let i = 0; i < this.options.classDetails.length; i++) {
            if (this.options.classDetails[i].id === id) {
                classDetail = this.options.classDetails[i];
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
    _findSpecById(id) {
        let spec = null;

        for (let i = 0; i < this.options.specializations.length; i++) {
            if (this.options.specializations[i].id === id) {
                spec = this.options.specializations[i];
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
    _isClassPartOfRace(raceId, classId) {
        let race = this._findRaceById(raceId);

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
    _isSpecPartOfClass(classId, specId) {
        let classDetail = this._findClassById(classId);

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
    _factionChanged(changeEvent) {
        // console.log('>> _factionChanged');
        let newFactionId = parseInt($('#faction_id').val());

        // Keep a list of classselects
        let $classSelects = $('select.classselect');

        let self = this;

        // For each race select there is ..
        $.each($('select.raceselect'), function (index, select) {
            let $select = $(select);
            let $classSelect = $($classSelects[index]);
            let currentClassId = parseInt($classSelect.val());

            // Adjust current selections in race if the faction changed, if faction was Alliance with a bunch
            // of Alliance races, faction switched to Horde, put all those selects to -1
            let currentRaceId = parseInt($select.val());
            let currentRace = self._findRaceById(currentRaceId);
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
                    ((currentClassId > 0 && !self._isClassPartOfRace(optionRaceId, currentClassId)) ||
                        // Race does not belong to the new faction, or new faction was unspecified
                        self._findRaceById(optionRaceId).faction_id !== newFactionId && newFactionId !== 1)) {
                    $option.hide();
                } else {
                    $option.show();
                }
            });
        });
        // console.log('OK _factionChanged');
    }

    /**
     *
     * @param changeEvent
     * @private
     */
    _raceChanged(changeEvent) {
        // console.log('>> _raceChanged');
        let self = this;

        // Changed by user
        if (changeEvent.originalEvent) {
            let $raceSelect = $(changeEvent.target);
            let newRaceId = parseInt($raceSelect.val());

            let $classSelect = $('.classselect').find("[data-id='" + $raceSelect.data('id') + "']");

            if (newRaceId > 0) {
                // Check if the current class we've selected is still valid with this new race
                let currentClassId = parseInt($classSelect.val());

                if (currentClassId > 0 && currentClassId !== 0 && !self._isClassPartOfRace(newRaceId, currentClassId)) {
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
                if (newRaceId > 0 && optionClassId > 0 && !self._isClassPartOfRace(newRaceId, parseInt(optionClassId))) {
                    $option.hide();
                } else {
                    $option.show();
                }
            });

            // Change faction to appropriate faction based on faction of race
            if (newRaceId > 0) {
                let race = self._findRaceById(newRaceId);
                let raceFactionId = race.faction_id;

                let $faction = $('#faction_id');
                if (parseInt($faction.val()) !== raceFactionId) {
                    // Trigger change event
                    $faction.val(raceFactionId).change();
                }
            }
        }
        // console.log('OK _raceChanged');
    }

    /**
     *
     * @private
     */
    _classChanged(changeEvent) {
        // console.log('>> _classChanged');
        // Changed by user
        let $classSelect = $(changeEvent.target);
        let newClassId = parseInt($classSelect.val());

        let $raceSelect = $('.raceselect').find("[data-id='" + $classSelect.data('id') + "']");
        let self = this;

        // For each race select there is ..
        $.each($raceSelect.find('option'), function (index, value) {
            // Hide those options that aren't part of the faction
            let $option = $(value);

            let optionRaceId = parseInt($option.attr('value'));
            let optionRace = self._findRaceById(optionRaceId);

            let currentFactionId = parseInt($('#faction_id').val());
            // If it's not the first entry (Race...), and the candidate race is not part of the new class, and if the faction
            if (optionRaceId > 0 && (newClassId > 0 && !self._isClassPartOfRace(optionRaceId, newClassId) || (currentFactionId !== optionRace.faction_id && currentFactionId !== 0))) {
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
                if (currentSpecId > 0 && newClassId !== 0 && !self._isSpecPartOfClass(newClassId, currentSpecId)) {
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
                if (newClassId > 0 && optionSpecId > 0 && !self._isSpecPartOfClass(newClassId, parseInt(optionSpecId))) {
                    $option.hide();
                } else {
                    $option.show();
                }
            });
        }
        // console.log('OK _classChanged');
    }

    /**
     *
     * @param changeEvent
     * @private
     */
    _specializationChanged(changeEvent) {
        // console.log('>> _specializationChanged');
        let self = this;
        let $specSelect = $(changeEvent.target);
        let newSpecId = parseInt($specSelect.val());

        let $classSelect = $('.classselect').find("[data-id='" + $specSelect.data('id') + "']");
        // For each race select there is ..
        $.each($classSelect.find('option'), function (index, value) {
            // Hide the classes that do not have this spec (all but one)
            let $option = $(value);

            let optionClassId = parseInt($option.attr('value'));
            // If it's not the first entry (Spec...), and the candidate race is not part of the new class
            if (newSpecId > 0 && (optionClassId === 0 || (optionClassId > 0 && !self._isSpecPartOfClass(optionClassId, newSpecId)))) {
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
        // console.log('OK _specializationChanged');
    }

    /**
     * Fills the factions dropdown with all factions.
     * @private
     */
    _fillFactions() {
        let $factionSelect = $('#faction_id');

        // Remove existing options
        $factionSelect.find('option').remove();

        this._addIconOptionToSelect($factionSelect, this.options.factions, 'faction_icon_');
    }

    /**
     * Fills the relevant specialization select boxes with all available specializations
     * @private
     */
    _fillSpecializations() {
        let self = this;

        $.each($('select.specializationselect'), function (index, value) {
            let $specializationSelect = $(value);

            // Remove existing options
            $specializationSelect.find('option').remove();
            // Append default option
            $specializationSelect.append(jQuery('<option>', {
                value: '0', // Laravel can then accept values that haven't been set
                text: 'Specialization...'
            }));

            self._addIconOptionToSelect($specializationSelect, self.options.specializations, function (item) {
                let classDetails = self._findClassById(item.character_class_id);
                return 'spec_icon_' + classDetails.name.replace(/ /g, '').toLowerCase() + '-' + item.name.replace(/ /g, '').toLowerCase();
            });
        });
    }

    /**
     * Fills the relevant class select boxes with all available classes.
     * @private
     */
    _fillClasses() {
        let self = this;

        $.each($('select.classselect'), function (index, value) {
            let $classSelect = $(value);

            // Remove existing options
            $classSelect.find('option').remove();
            // Append default option
            $classSelect.append(jQuery('<option>', {
                value: '0', // Laravel can then accept values that haven't been set
                text: 'Class...'
            }));

            self._addIconOptionToSelect($classSelect, self.options.classDetails, 'class_icon_');
        });
    }

    /**
     * Fills the relevant race select boxes with all available races.
     * @private
     */
    _fillRaces() {
        let self = this;

        $.each($('select.raceselect'), function (index, value) {
            let $raceSelect = $(value);

            // Remove existing options
            $raceSelect.find('option').remove();
            // Append default option
            $raceSelect.append(jQuery('<option>', {
                value: '0', // Laravel can then accept values that haven't been set
                text: 'Race...'
            }));

            self._addIconOptionToSelect($raceSelect, self.options.races, function (item) {
                let raceDetails = self._findRaceById(item.id);
                return 'faction_icon_' + self._findFactionById(raceDetails.faction_id).name.replace(/ /g, '').toLowerCase();
            });
        });
    }

    /**
     * Adds a list of options to a select based on an object collection.
     * @param $select
     * @param dataCollection
     * @private
     */
// _addOptionToSelect($select, dataCollection) {
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
    _addIconOptionToSelect($select, dataCollection, cssPrefix = '') {
        // Append the rest of the options
        for (let i = 0; i < dataCollection.length; i++) {
            let obj = dataCollection[i];

            let template = Handlebars.templates['composition_icon_option_template'];

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
                $(option).attr('data-content', template(data))
            );
        }
    }

    /**
     * Load defaults that were set by a dungeon route.
     * @private
     */
    _loadDungeonRouteDefaults() {
        let $faction = $("#faction_id");
        $faction.val(_oldFaction);
        // Have to manually trigger change..
        $faction.trigger('change');

        let $specializationsSelects = $(".specializationselect select");
        let $racesSelects = $(".raceselect select");
        let $classSelects = $(".classselect select");

        // For each specialization
        for (let i = 0; i < _oldSpecializations.length; i++) {
            let characterSpecialization = _oldSpecializations[i];
            let $specializationSelect = $($specializationsSelects[i]);
            $specializationSelect.val(characterSpecialization.id);
            // Have to manually trigger change..
            $specializationSelect.trigger('change');
        }

        // For each class
        for (let i = 0; i < _oldClasses.length; i++) {
            let characterClass = _oldClasses[i];
            let $classSelect = $($classSelects[i]);
            $classSelect.val(characterClass.id);
            // Have to manually trigger change..
            $classSelect.trigger('change');
        }

        // For each race
        for (let i = 0; i < _oldRaces.length; i++) {
            let race = _oldRaces[i];
            let $raceSelect = $($racesSelects[i]);
            $raceSelect.val(race.id);
            // Have to manually trigger change..
            $raceSelect.trigger('change');
        }

        refreshSelectPickers();
    }
}