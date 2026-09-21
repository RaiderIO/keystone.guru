<?php
/**
 * The "Add to collection…" dialog for one of the current user's own routes. Include it once per page; every element
 * matching $triggerSelector with a data-publickey attribute opens it for that route.
 *
 * @var string $triggerSelector
 */

$modalId = 'add_to_collection_modal';
?>
@component('common.general.modal', ['id' => $modalId, 'keyboard' => true, 'labelledBy' => sprintf('%s_title', $modalId)])
    <h3 id="{{ $modalId }}_title" class="card-title">{{ __('view_common.collection.addtocollection.title') }}</h3>
    <p class="text-body-secondary small">{{ __('view_common.collection.addtocollection.help') }}</p>

    <div id="{{ $modalId }}_status" class="text-body-secondary mb-2" role="status" aria-live="polite"></div>
    <ul id="{{ $modalId }}_list" class="list-group mb-3" hidden></ul>
    <div id="{{ $modalId }}_new"></div>
@endcomponent

@include('common.general.inline', [
    'path' => 'common/collection/addtocollection',
    'options' => [
        'triggerSelector' => $triggerSelector,
        'modalSelector' => sprintf('#%s', $modalId),
        'statusSelector' => sprintf('#%s_status', $modalId),
        'listSelector' => sprintf('#%s_list', $modalId),
        'newSelector' => sprintf('#%s_new', $modalId),
        'forDungeonRouteUrl' => route('ajax.collections.fordungeonroute'),
    ],
])
