<?php
/** @var $user \App\User */
?>
<div class="tab-pane fade" id="patreon" role="tabpanel" aria-labelledby="patreon-tab">
    <h4>
        {{ __('views/profile.edit.patreon') }}
    </h4>
    @isset($user->patreondata)
        <a class="btn patreon-color text-white" href="{{ route('patreon.unlink') }}" target="_blank"
           rel="noopener noreferrer">
            {{ __('views/profile.edit.unlink_from_patreon') }}
        </a>

        <p class="mt-2">
            <span class="text-info"><i class="fa fa-check-circle"></i></span>
            {{ __('views/profile.edit.link_to_patreon_success') }}
        </p>

        <table class="default_table table-striped">
            <tr>
                <th class="pl-1">
                    {{ __('views/profile.edit.paid_tier_table.header_active') }}
                </th>
                <th>
                    {{ __('views/profile.edit.paid_tier_table.header_tier') }}
                </th>
            </tr>
            @foreach(\App\Models\PaidTier::all() as $paidTier)
                    <?php /** @var $paidTier \App\Models\PaidTier */ ?>
                <tr>
                    <td class="pl-1">
                        <i class="fas fa-{{ $user->hasPaidTier($paidTier->key) ? 'check-circle text-success' : 'times-circle text-danger' }}"></i>
                    </td>
                    <td>
                        {{ __($paidTier->name) }}
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
                            'state' => csrf_token()
                            ])
                        }}" target="_blank" rel="noopener noreferrer">
            {{ __('views/profile.edit.link_to_patreon') }}
        </a>

        <p class="mt-2">
            <span class="text-info"><i class="fa fa-info-circle"></i></span>
            {{ __('views/profile.edit.link_to_patreon_description') }}
        </p>
    @endisset
</div>
