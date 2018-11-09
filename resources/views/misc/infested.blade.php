<?php
/** @var \App\Models\GameServerRegion $region */
$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
$currentAffixGroup = $region->getCurrentAffixGroup();
?>
@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Infested mapping')])

@section('header-title', __('Infested Mapping Progress'))

@section('content')
    <div class="container">
        @auth
            <a class="btn btn-primary text-white w-100" role="button" data-toggle="modal"
               data-target="#infested_voting_modal">
                <i class="fas fa-vote-yea"></i> {{__('Start voting!')}}
            </a>
        @endauth
        <h2 class="mt-4">
            {{ __('What is this page?') }}
        </h2>
        <p>
            As you may be aware, Infested enemies change every week from a subset of enemies hand-picked by Blizzard.
            This
            makes mapping Infested enemies on Keystone.guru a challenge since valid data changes rapidly. Especially
            since I
            personally don't do 10+ keystones yet and my time is limited enough as-is to comb through streams for all
            Infested enemies. For this, I have made a system that allows users of Keystone.guru to contribute to mapping
            Infested enemies and correct any errors that may arise.
        </p>
        <h2>
            {{ __('How can I contribute to the Infested mapping?') }}
        </h2>
        <p>
            I have written a guide on
            <a href="https://www.reddit.com/r/KeystoneGuru/comments/9t8ctx/the_site_has_infested_support_heres_how_to_use_it/">Reddit
                <i class="fas fa-external-link-alt"></i></a> on how to contribute to the Infested mapping. I will
            continue to improve the system so that it's the easiest it can be for people to contribute to the mapping.
            If you have feedback please let me know!
        </p>

        <div class="form-group">
            <h2>{{ __('Infested mapping progress') . sprintf(' (%s)', $currentAffixGroup->getTextAttribute()) }}</h2>
            <table id="infested_mapping_progress_table" class="tablesorter default_table table-striped">
                <thead>
                <tr>
                    <th width="55%">{{ __('Dungeon') }}</th>
                    <th width="15%">{{ __('Infested enemies') }}</th>
                    <th width="15%">{{ __('Yes votes cast') }}</th>
                    <th width="15%">{{ __('No votes cast') }}</th>
                </tr>
                </thead>

                <tbody>
                @php($infestedEnemyStatus = \App\Models\Dungeon::getInfestedEnemyStatus($currentAffixGroup->id))
                @foreach(\App\Models\Dungeon::active()->get() as $dungeon )
                    @php( $hasData = isset($infestedEnemyStatus[$dungeon->id]) )
                    @php( $data = $hasData ? $infestedEnemyStatus[$dungeon->id] : [] )
                    <tr>
                        <td>{{ $dungeon->name }}</td>
                        <td>{{ number_format($hasData ? $data->infested_enemies : 0, 0) }}</td>
                        <td>{{ number_format($hasData ? $data->infested_yes_votes : 0, 0) }}</td>
                        <td>{{ number_format($hasData ? $data->infested_no_votes : 0, 0) }}</td>
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
                    <th width="70%">{{ __('User') }}</th>
                    <th width="15%">{{ __('Yes votes cast') }}</th>
                    <th width="15%">{{ __('No votes cast') }}</th>
                    {{--<th width="15%">{{ __('Enemies marked as infested') }}</th>--}}
                </tr>
                </thead>

                @php($voteHoF = \App\User::getInfestedEnemyHoF($currentAffixGroup->id))
                <tbody>
                @foreach($voteHoF as $userVote )
                    @if($userVote->infested_yes_votes > 0)
                        <tr>
                            <td>{{ $userVote->name }}</td>
                            <td>{{ number_format($userVote->infested_yes_votes) }}</td>
                            <td>{{ number_format($userVote->infested_no_votes) }}</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-group">
            <h2>{{ __('User vote hall of fame (current season)') }}</h2>
            <table id="infested_mapping_user_hall_of_fame_table" class="tablesorter default_table table-striped">
                <thead>
                <tr>
                    <th width="70%">{{ __('User') }}</th>
                    <th width="15%">{{ __('Yes votes cast') }}</th>
                    <th width="15%">{{ __('No votes cast') }}</th>
                    {{--<th width="15%">{{ __('Enemies marked as infested') }}</th>--}}
                </tr>
                </thead>

                @php($voteHoF = \App\User::getInfestedEnemyHoF())
                <tbody>
                @foreach($voteHoF as $userVote )
                    @if($userVote->infested_yes_votes > 0)
                        <tr>
                            <td>{{ $userVote->name }}</td>
                            <td>{{ number_format($userVote->infested_yes_votes) }}</td>
                            <td>{{ number_format($userVote->infested_no_votes) }}</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-group">
            <h2>{{ __('Resources') }}</h2>
            {!!  sprintf(
            __('%s runs an amazing stream running mostly M+ in which a lot of information can be found (including
            Infested enemies).'),
            '<a href="https://www.twitch.tv/shakibdh/">shakibdh <i class="fas fa-external-link-alt"></i></a>') !!}
        </div>

        @auth
            <a class="btn btn-primary text-white w-100 mt-4" role="button" data-toggle="modal"
               data-target="#infested_voting_modal">
                <i class="fas fa-vote-yea"></i> {{__('Start voting!')}}
            </a>
        @endauth
    </div>


    @auth
        <!-- Modal infested voting -->
        <div class="modal fade" id="infested_voting_modal" tabindex="-1" role="dialog"
             aria-hidden="true">
            <div class="modal-dialog modal-md vertical-align-center">
                <div class="modal-content">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="probootstrap-modal-flex">
                        <div class="probootstrap-modal-content">
                            <div class="container">
                                @include('common.forms.infestedvoting')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END modal infested voting -->
    @endauth
@endsection