<?php

use App\Models\User;

/**
 * @var User $user
 */

$deleteConsequences = $user->getDeleteConsequences();
?>
<div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
    <h4>
        {{ __('view_profile.edit.account') }}
    </h4>
    <div class="form-group">
        {{ __('view_profile.edit.account_delete_consequences') }}:
    </div>
    @if( !empty($deleteConsequences['dungeonroutes']) && $deleteConsequences['dungeonroutes']['delete_count'] > 0 )
        <div class="form-group">
            <h5>
                {{ __('view_profile.edit.account_delete_consequence_routes') }}
            </h5>
            <ul>
                <li>
                    {{ sprintf(__('view_profile.edit.account_delete_consequence_routes_delete'), $deleteConsequences['dungeonroutes']['delete_count']) }}
                </li>
            </ul>
        </div>
    @endif
    @if( !empty($deleteConsequences['teams']) )
        <div class="form-group">
            <h5>
                {{ __('view_profile.edit.account_delete_consequence_teams') }}
            </h5>
            <ul>
                    <?php foreach ($deleteConsequences['teams'] as $teamName => $consequence) { ?>
                <li>
                        <?php
                        $consequenceText = '';
                        if ($consequence['result'] === 'new_owner') {
                            if ($consequence['new_owner'] === null) {
                                $consequenceText = __('view_profile.edit.account_delete_consequence_teams_you_are_removed');
                            } else {
                                $consequenceText = sprintf(__('view_profile.edit.account_delete_consequence_teams_new_admin'),
                                    $consequence['new_owner']->name);
                            }
                        } else if ($consequence['result'] === 'deleted') {
                            $consequenceText = __('view_profile.edit.account_delete_consequence_teams_team_deleted');
                        }
                        ?>
                    {{ sprintf('%s: %s', $teamName, $consequenceText) }}
                </li>
                <?php }
                    ?>
            </ul>
        </div>
    @endif
    @if( !empty($deleteConsequences['patreon']) && $deleteConsequences['patreon']['unlinked'] )
        <div class="form-group">
            <h5>
                {{ __('view_profile.edit.patreon') }}
            </h5>
            <ul>
                <li>
                    {{ __('view_profile.edit.account_delete_consequence_patreon') }}
                </li>
            </ul>
        </div>
    @endif
    @if( !empty($deleteConsequences['reports']))
        <div class="form-group">
            <h5>
                {{ __('view_profile.edit.reports') }}
            </h5>
            <ul>
                <li>
                    {{ sprintf(__('view_profile.edit.account_delete_consequence_reports_unresolved'), $deleteConsequences['reports']['delete_count']) }}
                </li>
            </ul>
        </div>
    @endif
    <div class="text-danger font-weight-bold">
        {{ __('view_profile.edit.account_delete_warning') }}
    </div>
    {{ html()->form('POST', route('profile.delete'))->open() }}
    {{ html()->hidden('_method', 'delete') }}
    {{ html()->input('submit')->value(__('view_profile.edit.account_delete_confirm'))->class('btn btn-danger')->name('submit') }}
    {{ html()->form()->close() }}
</div>
