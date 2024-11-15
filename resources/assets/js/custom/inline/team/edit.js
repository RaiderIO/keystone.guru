class TeamEdit extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);
        this._dt = null;
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;
        let code = _inlineManager.getInlineCodeById(this.options.routesTableInlineId);
        let tableView = code.getTableView();
        tableView.setIsUserModerator(this.options.userIsModerator);

        $('#team_invite_link_copy_to_clipboard').unbind('click').bind('click', function () {
            copyToClipboard($('#team_members_invite_link').val());
        });

        // Refresh invite link
        $('#team_invite_link_refresh').unbind('click').bind('click', function () {
            self.refreshInviteLink();
        });

        // Add route to team button - only if enabled (user is Moderator)
        $('#add_route_btn:enabled').unbind('click').bind('click', function () {
            tableView.setAddMode(true);

            code.refreshTable();
            $(this).hide();
            $('#view_existing_routes').show();
        });

        // Cancel button when done adding routes
        $('#view_existing_routes').unbind('click').bind('click', function () {
            tableView.setAddMode(false);

            code.refreshTable();
            $(this).hide();
            $('#add_route_btn').show();
        });

        $('#delete_team').unbind('click').bind('click', function (clickEvent) {
            showConfirmYesCancel(lang.get('messages.delete_team_confirm_label'), function () {
                // Change the method to DELETE
                $('#details [name="_method"]').val('DELETE');
                // Submit the form
                $('#details form').submit();
            }, null, {type: 'error'});

            clickEvent.preventDefault();
        });

        this.refreshTable();

        // Fix members data table being in a separate tab ignoring width
        // https://datatables.net/examples/api/tabs_and_scrolling.html
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
        });

        $('select.role_selection').bind('change', function (e) {
            $.ajax({
                type: 'PUT',
                url: `/ajax/team/${self.options.teamPublicKey}/changerole`,
                dataType: 'json',
                data: {
                    username: $(this).data('username'),
                    role: $(this).val()
                },
                success: function () {
                    showSuccessNotification(lang.get('messages.change_role_success'));
                }
            });
        });

        $('#default_role').bind('change', function (e) {
            $.ajax({
                type: 'PUT',
                url: `/ajax/team/${self.options.teamPublicKey}/changedefaultrole`,
                dataType: 'json',
                data: {
                    default_role: $(this).val()
                },
                success: function () {
                    showSuccessNotification(lang.get('messages.change_default_role_success'));
                }
            });
        });

        $(this.options.routePublishingEnabledSelector).on('change', function(){
            $.ajax({
                type: 'PUT',
                url: `/ajax/team/${self.options.teamPublicKey}/routepublishing`,
                dataType: 'json',
                data: {
                    enabled: parseInt($(this).val())
                },
                success: function () {
                    showSuccessNotification(lang.get('messages.change_route_publishing_success'));
                }
            });
        });
    }

    /**
     * Gets icon data for a role.
     * @param roleName The name of the role you want icon data for.
     * @returns {boolean}
     * @private
     */
    _getIcon(roleName) {
        // Matching roles to icons
        let icons = [{
            name: 'member',
            icon: 'fa-eye',
            label: lang.get('messages.team_member')
        }, {
            name: 'collaborator',
            icon: 'fa-edit',
            label: lang.get('messages.team_collaborator')
        }, {
            name: 'moderator',
            icon: 'fa-user-cog',
            label: lang.get('messages.team_moderator')
        }, {
            name: 'admin',
            icon: 'fa-crown',
            label: lang.get('messages.team_admin')
        }];

        let result = false;

        // For each role there exists
        for (let roleCandidateIndex in icons) {
            if (icons.hasOwnProperty(roleCandidateIndex)) {
                let roleCandidate = icons[roleCandidateIndex];
                // Match assignable role with candidate
                if (roleName === roleCandidate.name) {
                    // Found what we're looking for, push the result
                    result = roleCandidate;
                    break;
                }
            }
        }

        return result;
    }

    _grantAdFreeGiveaway(userId, add) {
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/team/${self.options.teamPublicKey}/member/${userId}/adfree`,
            data: add ? {} : {
                _method: 'DELETE'
            },
            dataType: 'json',
            failed: function () {
                if (add) {
                    showErrorNotification(lang.get('messages.ad_free_giveaway_add_failed'));
                } else {
                    showErrorNotification(lang.get('messages.ad_free_giveaway_remove_failed'));
                }
            },
            success: function () {
                if (add) {
                    showSuccessNotification(lang.get('messages.ad_free_giveaway_add_success'));
                } else {
                    showSuccessNotification(lang.get('messages.ad_free_giveaway_remove_success'));
                }

                // Give user a second to read the notification
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
        });
    }

    _removeAdFreeGiveaway

    _removeUserFromTeam(userId) {
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/team/${self.options.teamPublicKey}/member/${userId}`,
            data: {
                _method: 'DELETE'
            },
            dataType: 'json',
            success: function () {

                // Remove the user from the data
                for (let index in self.options.data) {
                    // If we found the index in the data of the row we just removed..
                    if (self.options.data.hasOwnProperty(index) && self.options.data[index].user_id === userId) {
                        // Remove it from the data array
                        self.options.data.splice(index, 1);
                        break;
                    }
                }

                // Redirect to the home page
                if (userId === self.options.currentUserId) {
                    window.location.href = '/';
                } else {
                    showSuccessNotification(lang.get('messages.remove_member_success'));

                    self.refreshTable();
                }
            }
        });
    }

    /**
     * Refreshes the table with the current data.
     */
    refreshTable() {
        let self = this;

        let $table = $('#team_members_table');
        if (this._dt !== null) {
            this._dt.destroy();
            $table.empty();
        }

        let columns = [{
            'data': 'name',
            'title': lang.get('messages.name_label'),
            'width': '45%'
        }, {
            'data': 'join_date',
            'title': lang.get('messages.join_date_label'),
            'width': '20%',
            'className': 'd-none d-lg-table-cell'
        }];

        // Only admins/moderators have the option to remove members from a team
        if (self.options.userIsModerator) {
            columns.push({
                'data': 'assignable_roles',
                'title': lang.get('messages.assignable_roles_label'),
                'width': '20%',
                'render': function (data, type, row, meta) {
                    let roles = [];

                    // Match the valid roles with roles above
                    let assignableRoles = row.assignable_roles;
                    for (let roleIndex in assignableRoles) {
                        if (assignableRoles.hasOwnProperty(roleIndex)) {
                            // Fetch the role..
                            let assignableRole = assignableRoles[roleIndex];

                            let icon = self._getIcon(assignableRole);
                            if (icon !== false) {
                                roles.push(icon);
                            }
                        }
                    }

                    let result;
                    if (roles.length === 0) {
                        let icon = self._getIcon(data);

                        // Handlebars the entire thing
                        let template = Handlebars.templates['team_member_table_permissions_self_template'];
                        let templateData = $.extend({}, getHandlebarsDefaultVariables(), {
                            icon: icon.icon,
                            label: icon.label,
                            self: self.options.currentUserName === row.name
                        });

                        result = template(templateData);
                    } else {
                        // Handlebars the entire thing
                        let template = Handlebars.templates['team_member_table_permissions_template'];
                        let templateData = $.extend({}, getHandlebarsDefaultVariables(), {
                            username: row.name,
                            role: row.role,
                            is_admin: row.role === 'admin',
                            roles: roles,
                            self: self.options.currentUserName === row.name
                        });

                        result = template(templateData);
                    }
                    return result;
                },
                'orderable': false
            });
        } else {
            columns.push({
                'data': 'role',
                'title': lang.get('messages.role_label'),
                'width': '20%',
                'render': function (data, type, row, meta) {
                    return _.startCase(_.toLower(row.role));
                },
                'orderable': true
            });
        }

        columns.push({
            'data': 'join_date',
            'title': lang.get('messages.actions_label'),
            'width': '15%',
            'render': function (data, type, row, meta) {
                let result = '';
                let template = null;
                if (row.user_id === self.options.currentUserId) {
                    // Handlebars the entire thing
                    template = Handlebars.templates['team_member_table_actions_self_template'];
                    // Admins can remove all - but moderators cannot remove admins
                } else if (self.options.userIsModerator && ((self.options.currentUserRole === 'moderator' && row.role !== 'admin') || self.options.currentUserRole === 'admin')) {
                    // Handlebars the entire thing
                    template = Handlebars.templates['team_member_table_actions_template'];
                }

                if (template !== null) {
                    let userData = self.getDataByUserId(row.user_id);

                    let templateData = $.extend({}, getHandlebarsDefaultVariables(), {
                        user_id: row.user_id,
                        can_current_user_ad_free_giveaway: self.options.adFreeGiveawayLeft > 0,
                        has_ad_free: userData.has_ad_free,
                        has_ad_free_giveaway: userData.has_ad_free_giveaway,
                        has_ad_free_giveaway_by_current_user: userData.has_ad_free_giveaway_by_current_user,
                    });

                    result = template(templateData);
                }
                return result;
            }
        });

        this._dt = $table.DataTable({
            'data': this.options.data,
            'searching': false,
            'bLengthChange': false,
            'columns': columns,
            'language': {
                'emptyTable': lang.get('messages.datatable_no_members_in_table')
            }
        });

        $('.ad_free_giveaway_add').unbind('click').bind('click', function (e) {
            self._grantAdFreeGiveaway(parseInt($(this).data('userid')), true);
        });

        $('.ad_free_giveaway_remove').unbind('click').bind('click', function (e) {
            self._grantAdFreeGiveaway(parseInt($(this).data('userid')), false);
        });

        $('.remove_user_btn').unbind('click').bind('click', function (e) {
            let userId = parseInt($(this).data('userid'));
            showConfirmYesCancel(lang.get('messages.remove_member_confirm_label'), function () {
                self._removeUserFromTeam(userId);
            }, null, {type: 'error'});
        });

        $('.leave_team_btn').unbind('click').bind('click', function (e) {
            let userId = parseInt($(this).data('userid'));
            showConfirmYesCancel(lang.get(self.options.data.length === 1 ?
                'messages.leave_team_disband_confirm_label' :
                'messages.leave_team_confirm_label'), function () {
                self._removeUserFromTeam(userId);
            }, null, {type: 'error'});
        });
    }

    /**
     *
     * @param {Number} userId
     * @returns {Array|null}
     */
    getDataByUserId(userId) {
        let result = null;

        for (let index in this.options.data) {
            let userData = this.options.data[index];
            if (userData.user_id === userId) {
                result = userData;
                break;
            }
        }

        return result;
    }

    refreshInviteLink() {
        $.ajax({
            type: 'GET',
            url: `/ajax/team/${this.options.teamPublicKey}/refreshlink`,
            dataType: 'json',
            success: function (response) {
                $('#team_members_invite_link').val(response.new_invite_link);

                showInfoNotification(lang.get('messages.invite_link_refreshed'));
            }
        });

    }
}
