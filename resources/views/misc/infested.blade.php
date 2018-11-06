<?php
/** @var \App\Models\GameServerRegion $region */
$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
$currentAffixGroup = $region->getCurrentAffixGroup();
?>
@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Infested mapping')])

@section('header-title', __('Infested Mapping Progress'))

@section('content')
    <h2>
        {{ __('What is this page?') }}
    </h2>
    <p>
        As you may be aware, Infested enemies change every week from a subset of enemies hand-picked by Blizzard. This
        makes mapping Infested enemies on Keystone.guru a challenge since valid data changes rapidly. Especially since I
        personally don't do 10+ keystones yet and my time is limited enough as-is to comb through streams for all
        Infested enemies. For this, I have made a system that allows users of Keystone.guru to contribute to mapping
        Infested enemies and correct any errors that may arise.
    </p>
    <h2>
        {{ __('How can I contribute to the Infested mapping?') }}
    </h2>
    <p>
        I have written a guide on
        <a href="https://www.reddit.com/r/KeystoneGuru/comments/9t8ctx/the_site_has_infested_support_heres_how_to_use_it/">Reddit</a>
        on how to contribute to the Infested mapping. I will continue to improve the system so that it's the easiest it
        can be for people to contribute to the mapping.
    </p>

    <div class="form-group">
        <h2>{{ __('Infested mapping progress') . sprintf(' (%s)', $currentAffixGroup->getTextAttribute()) }}</h2>
        <table id="infested_mapping_progress_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="55%">{{ __('Dungeon') }}</th>
                <th width="15%">{{ __('Infested enemies') }}</th>
                <th width="15%">{{ __('No votes cast') }}</th>
                <th width="15%">{{ __('Yes votes cast') }}</th>
            </tr>
            </thead>

            <tbody>
            @foreach(\App\Models\Dungeon::getInfestedEnemyStatus($currentAffixGroup->id) as $infestedEnemyStatus )
                <tr>
                    <td>{{ $infestedEnemyStatus->name }}</td>
                    <td>0</td>
                    <td>{{ $infestedEnemyStatus->infested_no_votes }}</td>
                    <td>{{ $infestedEnemyStatus->infested_yes_votes }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h2>{{ __('User vote hall of fame') . sprintf(' (%s)', $currentAffixGroup->getTextAttribute()) }}</h2>
        <table id="infested_mapping_user_hall_of_fame_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="55%">{{ __('User') }}</th>
                <th width="15%">{{ __('Votes cast') }}</th>
                <th width="15%">{{ __('Enemies marked as infested') }}</th>
            </tr>
            </thead>

            <tbody>
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h2>{{ __('User vote hall of fame (all time)') }}</h2>
    </div>
@endsection