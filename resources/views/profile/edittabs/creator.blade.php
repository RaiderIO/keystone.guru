<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use App\Models\UserPinnedDungeonRouteCollection;
use App\Models\UserSocialLinkPlatform;
use Illuminate\Support\Collection;

/**
 * @var User                          $user
 * @var Collection<int, DungeonRoute> $ownDungeonRoutes
 * @var array<int, int>               $pinnedDungeonRouteIds
 * @var Collection<int, DungeonRouteCollection> $ownDungeonRouteCollections
 * @var array<int, int>               $pinnedDungeonRouteCollectionIds
 */

$ownDungeonRouteCollections      ??= collect();
$pinnedDungeonRouteCollectionIds ??= [];

// After a failed validation both pickers must show what was submitted, not what is stored - otherwise
// resubmitting the corrected form silently saves the previous pins. An empty submission stays empty
if (session()->hasOldInput()) {
    $pinnedDungeonRouteIds           = array_map(intval(...), (array)old('pinned_dungeon_routes', []));
    $pinnedDungeonRouteCollectionIds = array_map(intval(...), (array)old('pinned_dungeon_route_collections', []));
}

$existingSocialLinks = $user->socialLinks->keyBy('platform');

/**
 * The help text and, when the field failed validation, its error message - what a field's aria-describedby lists.
 */
$describedBy = static fn(string $errorKey, string $errorId, string $helpId): string => trim(sprintf(
    '%s %s',
    $helpId,
    $errors->has($errorKey) ? $errorId : '',
));
?>
<div class="tab-pane fade" id="creator" role="tabpanel" aria-labelledby="creator-tab">
    <h4>
        {{ __('view_profile.edit.creator') }}
    </h4>

    <p class="creator_profile_edit_intro">
        {{ __('view_profile.edit.creator_description') }}
    </p>

    <p>
        <a href="{{ route('profile.view', ['user' => $user]) }}" class="btn btn-primary" role="button">
            <i class="fas fa-external-link-alt" aria-hidden="true"></i> {{ __('view_profile.edit.creator_view_public_profile') }}
        </a>
    </p>

    <p class="creator_profile_edit_url">
        {{ __('view_profile.edit.creator_public_profile_url') }}
        <code class="user-select-all">{{ rawurldecode(route('profile.view', ['user' => $user])) }}</code>
        <br>
        <small class="text-muted">{{ __('view_profile.edit.creator_public_profile_url_help') }}</small>
    </p>

    @include('common.general.messages')

    {{ html()->modelForm($user, 'PATCH', route('profile.creator.update'))->open() }}

    <div class="mb-3{{ $errors->has('bio') ? ' has-error' : '' }}">
        {{ html()->label(__('view_profile.edit.creator_bio'), 'bio') }}
        {{ html()->textarea('bio', $user->bio)
            ->class('form-control')
            ->rows(4)
            ->attribute('maxlength', 500)
            ->placeholder(__('view_profile.edit.creator_bio_placeholder'))
            ->attributeIf($errors->has('bio'), 'aria-invalid', 'true')
            ->attribute('aria-describedby', $describedBy('bio', 'bio_error', 'bio_help')) }}
        <small id="bio_help" class="form-text text-muted">
            {{ __('view_profile.edit.creator_bio_help', ['max' => 500]) }}
        </small>
        @include('common.forms.form-error', ['key' => 'bio', 'errorId' => 'bio_error'])
    </div>

    <h5 class="mt-4">
        {{ __('view_profile.edit.creator_socials') }}
    </h5>
    <p id="social_links_help" class="form-text text-muted">
        {{ __('view_profile.edit.creator_socials_help') }}
    </p>

    <div class="row g-3 mb-3">
        @foreach(UserSocialLinkPlatform::cases() as $platform)
            <?php
            $errorKey = sprintf('social_links.%s', $platform->value);
            $inputId  = sprintf('social_links_%s', $platform->value);
            $errorId  = sprintf('%s_error', $inputId);
            ?>
            <div class="col-12 col-lg-6{{ $errors->has($errorKey) ? ' has-error' : '' }}">
                <label for="{{ $inputId }}" class="form-label mb-1">
                    {{ __(sprintf('view_profile.view.platform.%s', $platform->value)) }}
                </label>
                <div class="input-group">
                    <span class="input-group-text" aria-hidden="true">
                        <i class="{{ $platform->icon() }} fa-fw"></i>
                    </span>
                    {{ html()->text(sprintf('social_links[%s]', $platform->value), $existingSocialLinks->get($platform->value)?->url)
                        ->id($inputId)
                        ->class('form-control')
                        ->attribute('inputmode', 'url')
                        ->attribute('autocomplete', 'url')
                        ->attributeIf($errors->has($errorKey), 'aria-invalid', 'true')
                        ->attribute('aria-describedby', $describedBy($errorKey, $errorId, 'social_links_help')) }}
                </div>
                @include('common.forms.form-error', ['key' => $errorKey, 'errorId' => $errorId])
            </div>
        @endforeach
    </div>

    <div class="mt-4 mb-3">
        @if($ownDungeonRoutes->isEmpty())
            <h5>{{ __('view_profile.edit.creator_pinned_routes') }}</h5>
            <p class="text-muted">
                {{ __('view_profile.edit.creator_pinned_routes_none') }}
            </p>
        @else
            @include('common.forms.orderedselect', [
                'id' => 'pinned_dungeon_routes',
                'name' => 'pinned_dungeon_routes',
                'label' => __('view_profile.edit.creator_pinned_routes'),
                'labelClass' => 'h5',
                'options' => $ownDungeonRoutes->mapWithKeys(static fn(DungeonRoute $ownDungeonRoute): array => [
                    $ownDungeonRoute->id => sprintf('%s — %s', $ownDungeonRoute->title, __($ownDungeonRoute->dungeon?->name ?? '')),
                ])->all(),
                'selectedIds' => $pinnedDungeonRouteIds,
                'max' => UserPinnedDungeonRoute::MAX_PINNED_ROUTES,
                'help' => __('view_profile.edit.creator_pinned_routes_help'),
                'emptyText' => __('view_profile.edit.creator_pinned_routes_empty'),
            ])
        @endif
    </div>

    <div class="mt-4 mb-3">
        @if($ownDungeonRouteCollections->isEmpty())
            <h5>{{ __('view_profile.edit.creator_pinned_collections') }}</h5>
            <p class="text-muted">
                {{ __('view_profile.edit.creator_pinned_collections_none') }}
            </p>
        @else
            @include('common.forms.orderedselect', [
                'id' => 'pinned_dungeon_route_collections',
                'name' => 'pinned_dungeon_route_collections',
                'label' => __('view_profile.edit.creator_pinned_collections'),
                'labelClass' => 'h5',
                'options' => $ownDungeonRouteCollections->mapWithKeys(static fn(DungeonRouteCollection $ownDungeonRouteCollection): array => [
                    $ownDungeonRouteCollection->id => $ownDungeonRouteCollection->dungeonRouteCollectionCategory === null
                        ? $ownDungeonRouteCollection->name
                        : sprintf('%s — %s', $ownDungeonRouteCollection->name, $ownDungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName()),
                ])->all(),
                'selectedIds' => $pinnedDungeonRouteCollectionIds,
                'max' => UserPinnedDungeonRouteCollection::MAX_PINNED_COLLECTIONS,
                'help' => __('view_profile.edit.creator_pinned_collections_help'),
                'emptyText' => __('view_profile.edit.creator_pinned_collections_empty'),
            ])
        @endif
    </div>

    <h5 class="mt-4">
        {{ __('view_profile.edit.creator_directory') }}
    </h5>

    <div class="mb-3{{ $errors->has('hide_from_creator_directory') ? ' has-error' : '' }}">
        <div class="form-check">
            {{ html()->checkbox('hide_from_creator_directory', $user->hide_from_creator_directory, 1)
                ->class('form-check-input')
                ->attribute('aria-describedby', 'hide_from_creator_directory_help') }}
            <label for="hide_from_creator_directory" class="form-check-label">
                {{ __('view_profile.edit.creator_directory_hide') }}
            </label>
        </div>
        <small id="hide_from_creator_directory_help" class="form-text text-muted d-block">
            {{ __('view_profile.edit.creator_directory_hide_help') }}
        </small>
        @include('common.forms.form-error', ['key' => 'hide_from_creator_directory'])
    </div>

    {{ html()->input('submit')->value(__('view_profile.edit.creator_save'))->class('btn btn-primary') }}
    {{ html()->closeModelForm() }}
</div>
