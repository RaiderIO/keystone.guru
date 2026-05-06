<?php

use App\Models\Season;

/**
 * @var Season|null $season
 * @var String[]    $shroudedBountyTypes
 * @var String[]    $affixes
 * @var bool        $isShrouded
 * @var bool        $isThundering
 * @var array       $raidBuffsOptions
 */
?>

        <!-- General settings -->
<div class="form-group">
    <label for="simulate_key_level">
        {{ __('view_common.modal.simulateoptions.default.key_level') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('view_common.modal.simulateoptions.default.key_level_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {{ html()->text('simulate_key_level', $season === null ? 2 : (int) ($season->key_level_min + $season->key_level_max) / 2)->id('simulate_key_level')->class('form-control') }}
        </div>
    </div>
</div>

@if($isShrouded)
    <div class="form-group">
        <label for="simulate_shrouded_bounty_type">
            {{ __('view_common.modal.simulateoptions.default.shrouded_bounty_type') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('view_common.modal.simulateoptions.default.shrouded_bounty_type_title') }}"></i>
        </label>
        <div class="row">
            <div class="col">
                {{ html()->select('simulate_shrouded_bounty_type', $shroudedBountyTypes)->id('simulate_shrouded_bounty_type')->class('form-control selectpicker')->data('none-selected-text', __('html.selectpicker.none_selected_text')) }}
            </div>
        </div>
    </div>
@else
    {{ html()->hidden('simulate_shrouded_bounty_type', 'none')->id('simulate_shrouded_bounty_type') }}
@endif

<div class="form-group row">
    <div class="col">
        <label for="simulate_affix">
            {{ __('view_common.modal.simulateoptions.default.affixes') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('view_common.modal.simulateoptions.default.affixes_title') }}"></i>
        </label>
        <div class="row">
            <div class="col">
                {{ html()->multiselect('simulate_affix', $affixes)->id('simulate_affix')->class('form-control selectpicker')->data('none-selected-text', __('html.selectpicker.none_selected_text')) }}
            </div>
        </div>
    </div>
    @if($isThundering)
        <div class="col">
            <label for="simulate_thundering">
                {{ __('view_common.modal.simulateoptions.default.simulate_thundering_clear_seconds') }}
                <i class="fas fa-info-circle" data-toggle="tooltip"
                   title="{{ __('view_common.modal.simulateoptions.default.simulate_thundering_clear_seconds_title') }}"></i>
            </label>
            <div class="row">
                <div class="col">
                    {{ html()->text('simulate_thundering_clear_seconds', '10')->id('simulate_thundering_clear_seconds')->class('form-control') }}
                </div>
            </div>
        </div>
    @else
        {{ html()->hidden('simulate_thundering_clear_seconds', '0')->id('simulate_thundering_clear_seconds') }}
    @endif
</div>

<div class="form-group row no-gutters">
    <div class="col">
        <label for="simulate_raid_buffs">
            {{ __('view_common.modal.simulateoptions.default.raid_buffs') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('view_common.modal.simulateoptions.default.raid_buffs_title') }}"></i>
        </label>
        <div class="row">
            <div class="col">
                {{ html()->multiselect('simulate_raid_buffs', $raidBuffsOptions)->id('simulate_raid_buffs')->class('form-control selectpicker')->data('none-selected-text', __('html.selectpicker.none_selected_text')) }}
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_hp_percent">
        {{ __('view_common.modal.simulateoptions.default.hp_percent') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('view_common.modal.simulateoptions.default.hp_percent_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {{ html()->text('simulate_hp_percent', '27')->id('simulate_hp_percent')->class('form-control') }}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_bloodlust_per_pull">
        {{ __('view_common.modal.simulateoptions.default.bloodlust_per_pull') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('view_common.modal.simulateoptions.default.bloodlust_per_pull_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {{ html()->multiselect('simulate_bloodlust_per_pull', [])->id('simulate_bloodlust_per_pull')->class('form-control selectpicker')->data('none-selected-text', __('html.selectpicker.none_selected_text')) }}
        </div>
    </div>
</div>
