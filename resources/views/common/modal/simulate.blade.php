<?php
/** @var $dungeonroute App\Models\DungeonRoute|null */

$shroudedBountyTypes = [];
foreach (\App\Models\SimulationCraft\SimulationCraftRaidEventsOptions::ALL_SHROUDED_BOUNTY_TYPES as $bountyType) {
    $shroudedBountyTypes[$bountyType] = __(sprintf('views/common.modal.simulate.shrouded_bounty_types.%s', $bountyType));
}

$affixes = [];
foreach (\App\Models\SimulationCraft\SimulationCraftRaidEventsOptions::ALL_AFFIXES as $affix) {
    $affixes[$affix] = __(sprintf('views/common.modal.simulate.affixes.%s', $affix));
}
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/simulate'])

<h3 class="card-title">{{ __('views/common.modal.simulate.title') }}</h3>

<!-- General settings -->
<div class="form-group">
    <label for="simulate_key_level">
        {{ __('views/common.modal.simulate.key_level') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::text('simulate_key_level', '15', ['id' => 'simulate_key_level', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_shrouded_bounty_type">
        {{ __('views/common.modal.simulate.shrouded_bounty_type') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::select('simulate_shrouded_bounty_type', $shroudedBountyTypes, null, ['id' => 'simulate_shrouded_bounty_type', 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_affix">
        {{ __('views/common.modal.simulate.affix') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::select('simulate_affix', $affixes, null, ['id' => 'simulate_affix', 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
</div>

<div class="form-group row">
    <div class="col">
        <label for="simulate_bloodlust">
            {{ __('views/common.modal.simulate.bloodlust') }}
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
    <label for="simulate_skill_loss_percent">
        {{ __('views/common.modal.simulate.skill_loss_percent') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::text('simulate_skill_loss_percent', '20', ['id' => 'simulate_skill_loss_percent', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="simulate_hp_percent">
        {{ __('views/common.modal.simulate.hp_percent') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::text('simulate_hp_percent', '27', ['id' => 'simulate_hp_percent', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group row">
    <div class="col">
        <div id="simulate_get_string" class="btn btn-success">
            <i class="fas fa-atom"></i> {{ __('views/common.modal.simulate.get_simulationcraft_string') }}
        </div>
    </div>
</div>

<div class="form-group">

    <div class="form-group">
        <div class="simulationcraft_export_loader_container" style="display: none;">
            <div class="d-flex justify-content-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('views/common.modal.simulate.loading') }}</span>
                </div>
            </div>
        </div>
        <div class="simulationcraft_export_result_container" style="display: none;">
            <label for="simulationcraft_export_result">
                {{ __('views/common.modal.simulate.simulationcraft_string') }}
            </label>
            <textarea id="simulationcraft_export_result" class="w-100" style="height: 400px" readonly></textarea>
        </div>
    </div>
    <div class="form-group">
        <div class="simulationcraft_export_result_container" style="display: none;">
            <div class="btn btn-info copy_simulationcraft_string_to_clipboard w-100">
                <i class="far fa-copy"></i> {{ __('views/common.modal.simulate.copy_to_clipboard') }}
            </div>
        </div>
    </div>
</div>
