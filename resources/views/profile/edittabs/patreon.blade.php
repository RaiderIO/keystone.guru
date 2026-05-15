<?php

use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;

/**
 * @var User $user
 */
?>
<div class="tab-pane fade" id="patreon" role="tabpanel" aria-labelledby="patreon-tab">
    <h4>
        {{ __('view_profile.edit.patreon') }}
    </h4>

    @include('common.general.messages')

    @if(isset($user->patreonUserLink) || $user->is_admin)
        @php(ob_start())
        @include('common.thirdparty.patreon.fancylink')
        @php($patreonLink = trim(ob_get_clean()))

        @if($user->is_admin)
            <p class="mt-2">
                <span class="text-info"><i class="fa fa-check-circle"></i></span>
                {!! __('view_profile.edit.patreon_status_for_admin', ['patreon' => $patreonLink]) !!}
            </p>
        @elseif($user->patreonUserLink->refresh_token === PatreonUserLink::PERMANENT_TOKEN)
            <p class="mt-2">
                <span class="text-info"><i class="fa fa-check-circle"></i></span>
                {!! __('view_profile.edit.patreon_status_granted_manually', ['patreon' => $patreonLink]) !!}
            </p>
        @else
            <a class="btn patreon-color text-white" href="{{ route('patreon.unlink') }}">
                {{ __('view_profile.edit.unlink_from_patreon') }}
            </a>

            <p class="mt-2">
                <span class="text-info"><i class="fa fa-check-circle"></i></span>
                {!! __('view_profile.edit.link_to_patreon_success', ['patreon' => $patreonLink]) !!}
            </p>
        @endif

        <table class="default_table table-striped">
            <tr>
                <th class="pl-1">
                    {{ __('view_profile.edit.patreon_benefit_table.header_active') }}
                </th>
                <th>
                    {{ __('view_profile.edit.patreon_benefit_table.header_benefit') }}
                </th>
            </tr>
            @foreach(PatreonBenefit::all() as $patreonBenefit)
                    <?php /** @var PatreonBenefit $patreonBenefit */ ?>
                <tr>
                    <td class="pl-1">
                        <i class="fas fa-{{ $user->hasPatreonBenefit($patreonBenefit->key) ? 'check-circle text-success' : 'times-circle text-danger' }}"></i>
                    </td>
                    <td>
                        {{ __($patreonBenefit->name) }}
                    </td>
                </tr>
            @endforeach

        </table>

        @php($patreonBenefits = $user->getPatreonBenefits())
        @if($patreonBenefits->contains(PatreonBenefit::AD_FREE_TEAM_MEMBERS) || $user->is_admin)
            <h4 class="mt-4">
                {{ __('view_profile.edit.ad_free_giveaway.title') }}
            </h4>

            <div class="form-group">
                @php($maxAdFreeGiveaways = config('keystoneguru.patreon.ad_free_giveaways'))
                @php($countLeft = PatreonAdFreeGiveaway::getCountLeft($user))
                @if($countLeft > 0)
                    {!! __('view_profile.edit.ad_free_giveaway.ad_free_giveaway_description_available', ['patreon' => $patreonLink, 'current' => $countLeft]) !!}
                @else
                    {!! __('view_profile.edit.ad_free_giveaway.ad_free_giveaway_description_not_available', ['patreon' => $patreonLink, 'max' => $maxAdFreeGiveaways]) !!}
                @endif
            </div>

            <table id="profile_ad_free_giveaway_table" class="default_table table-striped w-100">
                <thead>
                <tr>
                    <th>{{ __('view_profile.edit.ad_free_giveaway.table_header_team') }}</th>
                    <th>{{ __('view_profile.edit.ad_free_giveaway.table_header_member') }}</th>
                    <th>{{ __('view_profile.edit.ad_free_giveaway.table_header_ad_free') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($user->teams as $team)
                    @foreach($team->members as $member)
                        @if($member->id !== $user->id)
                            <tr>
                                <td>{{ $team->name }}</td>
                                <td>{{ $member->name }}</td>
                                <td>
                                    @include('common.forms.adfreegiveaway', ['user' => $member])
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach
                </tbody>
            </table>
        @endif
    @else
        <a class="btn patreon-color text-white" href="{{
                        'https://patreon.com/oauth2/authorize?' . http_build_query(
                            ['response_type' => 'code',
                            'client_id' => config('keystoneguru.patreon.oauth.client_id'),
                            'redirect_uri' => route('patreon.link'),
                            'scope' => config('keystoneguru.patreon.oauth.scope'),
                            'state' => csrf_token(),
                            ])
                        }}">
            {{ __('view_profile.edit.link_to_patreon') }}
        </a>

        <p class="mt-2">
            <span class="text-info"><i class="fa fa-info-circle"></i></span>
            {{ __('view_profile.edit.link_to_patreon_description') }}
        </p>
    @endisset
</div>
