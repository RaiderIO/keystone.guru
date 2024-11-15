<?php

use App\Models\Team;
use App\Models\TeamUser;

/**
 * @var Team $team
 * @var bool $userIsModerator
 * @var bool $userHasAdFreeTeamMembersPatreonBenefit
 * @var int  $userAdFreeTeamMembersRemaining
 * @var int  $userAdFreeTeamMembersMax
 */
?>

<div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
    <h4>
        {{ __('view_team.edittabs.members.title') }}
    </h4>
    <div class="form-group">
        @component('common.general.alert', ['type' => 'info', 'name' => 'team-invite-info'])
            {{ __('view_team.edittabs.members.invite_code_share_warning') }}
        @endcomponent

        <div class="row">
            <div class="col-xl-6">
                {!! Form::label('team_members_invite_link', __('view_team.edittabs.members.invite_new_members'), [], false) !!}
                <div class="input-group-append">
                    {!! Form::text('team_members_invite_link', route('team.invite', ['invitecode' => $team->invite_code]),
                        ['id' => 'team_members_invite_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                    <div class="input-group-append">
                        <button id="team_invite_link_copy_to_clipboard" class="btn btn-info"
                                data-toggle="tooltip"
                                title="{{ __('view_team.edittabs.members.copy_to_clipboard_title') }}">
                            <i class="far fa-copy"></i>
                        </button>
                        @if($userIsModerator)
                            <button id="team_invite_link_refresh" class="btn btn-info"
                                    data-toggle="tooltip"
                                    title="{{ __('view_team.edittabs.members.refresh_invite_link_title') }}">
                                <i class="fa fa-sync"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @if($userIsModerator)
                <div class="col-xl-6">
                    {!! Form::label('default_role', __('view_team.edittabs.members.default_role'), [], false) !!}
                    <?php $keys = array_keys(TeamUser::ALL_ROLES); ?>
                    {!! Form::select('default_role', array_map(function($role){
                            return __(sprintf('teamroles.%s', $role));
                        }, array_combine($keys, $keys)), $team->default_role, ['class' => 'form-control selectpicker']) !!}
                </div>
            @endif
        </div>
    </div>

    <div class="form-group">
                <span class="">
                    @php(ob_start())
                    @include('common.thirdparty.patreon.fancylink')
                    @php($patreonLink = trim(ob_get_clean()))
                    @if( $userHasAdFreeTeamMembersPatreonBenefit )
                        {!! __('view_team.edittabs.members.ad_free_giveaway_description_available', [
                            'patreon' => $patreonLink,
                            'current' => $userAdFreeTeamMembersRemaining,
                            'max' => $userAdFreeTeamMembersMax,
                        ]) !!}
                    @else
                        {!!  __('view_team.edittabs.members.ad_free_giveaway_description_not_available', [
                            'patreon' => $patreonLink,
                            'max' => $userAdFreeTeamMembersMax,
                        ]) !!}
                    @endif
                </span>
    </div>

    <div class="form-group">
        <table id="team_members_table" class="tablesorter default_table table-striped w-100">
            <thead>

            </thead>
        </table>
    </div>
</div>
