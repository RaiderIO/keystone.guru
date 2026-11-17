<?php

use App\Service\User\ApiTokenScope;
use Illuminate\Support\Collection;
use Laravel\Passport\Token;

/**
 * @var Collection<int, Token> $apiTokens
 * @var array<int, string>     $selectableApiTokenScopes
 */
$apiTokenPlainText = session('api_token_plain_text');
?>
<div class="tab-pane fade" id="api-tokens" role="tabpanel" aria-labelledby="api-tokens-tab">
    <h4>
        {{ __('view_profile.edittabs.apitokens.title') }}
    </h4>
    <p>
        {{ __('view_profile.edittabs.apitokens.description') }}
        <a href="{{ route('l5-swagger.public.api') }}">{{ __('view_profile.edittabs.apitokens.documentation') }}</a>
    </p>

    @include('common.general.messages')

    @if($apiTokenPlainText !== null)
        <div class="alert alert-warning">
            <p>{{ __('view_profile.edittabs.apitokens.copy_now') }}</p>
            <textarea id="api_token_plain_text" class="form-control font-monospace" rows="4" readonly>{{ $apiTokenPlainText }}</textarea>
        </div>
    @endif

    <h5 class="mt-4">{{ __('view_profile.edittabs.apitokens.create_title') }}</h5>
    {{ html()->form('POST', route('profile.apitoken.store'))->open() }}
    <div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_profile.edittabs.apitokens.name'), 'api_token_name')->class('form-label') }}
        {{ html()->text('name')->id('api_token_name')->class('form-control')->attribute('maxlength', 100)->required() }}
    </div>
    <div class="mb-3{{ $errors->has('scopes') || $errors->has('scopes.*') ? ' has-error' : '' }}">
        {{ html()->label(__('view_profile.edittabs.apitokens.scopes'))->class('form-label') }}
        @foreach($selectableApiTokenScopes as $scope)
            <div class="form-check">
                {{ html()->checkbox('scopes[]', false, $scope)->id(sprintf('api_token_scope_%s', str_replace(':', '_', $scope)))->class('form-check-input') }}
                {{ html()->label(sprintf('%s - %s', $scope, ApiTokenScope::from($scope)->description()), sprintf('api_token_scope_%s', str_replace(':', '_', $scope)))->class('form-check-label') }}
            </div>
        @endforeach
    </div>
    {{ html()->input('submit')->value(__('view_profile.edittabs.apitokens.create'))->class('btn btn-info') }}
    {{ html()->form()->close() }}

    <h5 class="mt-4">{{ __('view_profile.edittabs.apitokens.active_title') }}</h5>
    @if($apiTokens->isEmpty())
        <p>{{ __('view_profile.edittabs.apitokens.none') }}</p>
    @else
        <table class="default_table table-striped w-100">
            <thead>
            <tr>
                <th>{{ __('view_profile.edittabs.apitokens.table_header_name') }}</th>
                <th>{{ __('view_profile.edittabs.apitokens.table_header_scopes') }}</th>
                <th>{{ __('view_profile.edittabs.apitokens.table_header_created_at') }}</th>
                <th>{{ __('view_profile.edittabs.apitokens.table_header_expires_at') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($apiTokens as $apiToken)
                <tr>
                    <td>{{ $apiToken->name ?? $apiToken->client?->name }}</td>
                    <td>{{ implode(', ', $apiToken->scopes) }}</td>
                    <td>{{ $apiToken->created_at?->toDateString() }}</td>
                    <td>{{ $apiToken->expires_at?->toDateString() }}</td>
                    <td>
                        {{ html()->form('DELETE', route('profile.apitoken.destroy', ['tokenId' => $apiToken->id]))->open() }}
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> {{ __('view_profile.edittabs.apitokens.revoke') }}
                        </button>
                        {{ html()->form()->close() }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
