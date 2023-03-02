<?php
/** @var String[] $shroudedBountyTypes */
/** @var String[] $affixes */
/** @var bool $isShrouded */
/** @var bool $isThundering */
?>

    <!-- General settings -->
<div class="form-group">
    <label for="simulate_key_level">
        {{ __('views/common.modal.simulate.key_level') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('views/common.modal.simulate.key_level_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {!! Form::text('simulate_key_level', '15', ['id' => 'simulate_key_level', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

@if($isShrouded)
<div class="form-group">
    <label for="simulate_shrouded_bounty_type">
        {{ __('views/common.modal.simulate.shrouded_bounty_type') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('views/common.modal.simulate.shrouded_bounty_type_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {!! Form::select('simulate_shrouded_bounty_type', $shroudedBountyTypes, null, ['id' => 'simulate_shrouded_bounty_type', 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
</div>
@else
    {!! Form::hidden('simulate_shrouded_bounty_type', 'none', ['id' => 'simulate_shrouded_bounty_type']) !!}
@endif

@if($isThundering)
<div class="form-group row">
    <div class="col">
        <label for="simulate_affix">
            {{ __('views/common.modal.simulate.affix') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('views/common.modal.simulate.affix_title') }}"></i>
        </label>
        <div class="row">
            <div class="col">
                {!! Form::select('simulate_affix', $affixes, null, ['id' => 'simulate_affix', 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
    </div>
    <div class="col">
        <label for="simulate_thundering">
            {{ __('views/common.modal.simulate.simulate_thundering_clear_seconds') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('views/common.modal.simulate.simulate_thundering_clear_seconds_title') }}"></i>
        </label>
        <div class="row">
            <div class="col">
                {!! Form::text('simulate_thundering_clear_seconds', '10', ['id' => 'simulate_thundering_clear_seconds', 'class' => 'form-control']) !!}
            </div>
        </div>
    </div>
</div>
@else
    {!! Form::hidden('simulate_thundering_clear_seconds', '0', ['id' => 'simulate_thundering_clear_seconds']) !!}
@endif

<div class="form-group row no-gutters">
    <div class="col">
        <label for="simulate_bloodlust">
            {{ __('views/common.modal.simulate.bloodlust') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('views/common.modal.simulate.bloodlust_title') }}"></i>
        </label>
        <div class="row">
            <div class="col">
                {!! Form::checkbox('simulate_bloodlust', 1, null, ['id' => 'simulate_bloodlust', 'class' => 'form-control left_checkbox']) !!}
            </div>
        </div>
    </div>
    <div class="col">
        <label for="simulate_arcane_intellect">
            {{ __('views/common.modal.simulate.arcane_intellect') }}
        </label>
        <div class="row">
            <div class="col">
                {!! Form::checkbox('simulate_arcane_intellect', 1, null, ['id' => 'simulate_arcane_intellect', 'class' => 'form-control left_checkbox']) !!}
            </div>
        </div>
    </div>
    <div class="col">
        <label for="simulate_power_word_fortitude">
            {{ __('views/common.modal.simulate.power_word_fortitude') }}
        </label>
        <div class="row">
            <div class="col">
                {!! Form::checkbox('simulate_power_word_fortitude', 1, null, ['id' => 'simulate_power_word_fortitude', 'class' => 'form-control left_checkbox']) !!}
            </div>
        </div>
    </div>
    <div class="col">
        <label for="simulate_battle_shout">
            {{ __('views/common.modal.simulate.battle_shout') }}
        </label>
        <div class="row">
            <div class="col">
                {!! Form::checkbox('simulate_battle_shout', 1, null, ['id' => 'simulate_battle_shout', 'class' => 'form-control left_checkbox']) !!}
            </div>
        </div>
    </div>
    <div class="col">
        <label for="simulate_mystic_touch">
            {{ __('views/common.modal.simulate.mystic_touch') }}
        </label>
        <div class="row">
            <div class="col">
                {!! Form::checkbox('simulate_mystic_touch', 1, null, ['id' => 'simulate_mystic_touch', 'class' => 'form-control left_checkbox']) !!}
            </div>
        </div>
    </div>
    <div class="col">
        <label for="simulate_chaos_brand">
            {{ __('views/common.modal.simulate.chaos_brand') }}
        </label>
        <div class="row">
            <div class="col">
                {!! Form::checkbox('simulate_chaos_brand', 1, null, ['id' => 'simulate_chaos_brand', 'class' => 'form-control left_checkbox']) !!}
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_hp_percent">
        {{ __('views/common.modal.simulate.hp_percent') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('views/common.modal.simulate.hp_percent_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {!! Form::text('simulate_hp_percent', '27', ['id' => 'simulate_hp_percent', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_bloodlust_per_pull">
        {{ __('views/common.modal.simulate.bloodlust_per_pull') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('views/common.modal.simulate.bloodlust_per_pull_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {!! Form::select('simulate_bloodlust_per_pull', [], null, ['id' => 'simulate_bloodlust_per_pull', 'class' => 'form-control selectpicker', 'multiple' => 'multiple']) !!}
        </div>
    </div>
</div>
