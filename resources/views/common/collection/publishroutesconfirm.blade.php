<?php

use App\Models\DungeonRoute\DungeonRouteCollection;

/**
 * Confirmation shown right after a collection's own published state was raised, offering to raise every route in
 * it that is still less visible than the collection's new state. Only rendered when the controller found at least
 * one route the acting user is actually allowed to raise.
 *
 * @var DungeonRouteCollection $dungeonRouteCollection
 * @var string                 $publishedState      PublishedState name, e.g. "world".
 * @var string                 $publishedStateLabel  Translated label of $publishedState.
 */
?>
@component('common.general.modal', [
    'id' => 'collection_publish_routes_confirm_modal',
    'keyboard' => true,
    'active' => true,
    'labelledBy' => 'collection_publish_routes_confirm_modal_title',
])
    <h3 id="collection_publish_routes_confirm_modal_title" class="card-title">
        {{ __('view_collection.edit.publish_routes_confirm_title', ['state' => $publishedStateLabel]) }}
    </h3>
    <p class="text-body-secondary">
        {{ __('view_collection.edit.publish_routes_confirm_body', ['state' => $publishedStateLabel]) }}
    </p>
    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            {{ __('view_collection.edit.publish_routes_confirm_no') }}
        </button>
        <button type="button" id="collection_publish_routes_confirm_yes_button" class="btn btn-primary">
            {{ __('view_collection.edit.publish_routes_confirm_yes', ['state' => $publishedStateLabel]) }}
        </button>
    </div>
@endcomponent

@include('common.general.inline', ['path' => 'common/collection/publishroutesconfirm', 'options' => [
    'modalSelector'     => '#collection_publish_routes_confirm_modal',
    'yesButtonSelector' => '#collection_publish_routes_confirm_yes_button',
    'publishUrl'        => route('ajax.collection.routes.publish', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'publishedState'    => $publishedState,
]])
