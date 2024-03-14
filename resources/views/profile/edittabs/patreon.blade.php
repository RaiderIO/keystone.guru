<?php
/** @var $user \App\Models\User */
?>
<div class="tab-pane fade" id="patreon" role="tabpanel" aria-labelledby="patreon-tab">
    <h4>
        {{ __('view_profile.edit.patreon') }}
    </h4>
    @isset($user->patreonUserLink)
        @php(ob_start())
        @include('common.thirdparty.patreon.fancylink')
        @php($patreonLink = trim(ob_get_clean()))

        @if($user->patreonUserLink->refresh_token === \App\Models\Patreon\PatreonUserLink::PERMANENT_TOKEN)
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
            @foreach(\App\Models\Patreon\PatreonBenefit::all() as $patreonBenefit)
                    <?php /** @var $patreonBenefit \App\Models\Patreon\PatreonBenefit */ ?>
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
    @else
        <a class="btn patreon-color text-white" href="{{
                        'https://patreon.com/oauth2/authorize?' . http_build_query(
                            ['response_type' => 'code',
                            'client_id' => config('keystoneguru.patreon.oauth.client_id'),
                            'redirect_uri' => route('patreon.link'),
                            'scope' => config('keystoneguru.patreon.oauth.scope'),
                            'state' => csrf_token()
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
