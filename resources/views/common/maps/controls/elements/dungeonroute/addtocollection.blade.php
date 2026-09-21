<?php

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use Laravel\Pennant\Feature;

/**
 * Collections hold their owner's own routes, so only the author is offered "Add to collection…".
 *
 * @var DungeonRoute $dungeonroute
 **/

$showAddToCollection = $dungeonroute->author_id === Auth::id() && !$dungeonroute->isSandbox() && Feature::active(CreatorProfiles::class);
?>
@if($showAddToCollection)
    <div class="row g-0">
        <div class="col">
            <button id="add_to_collection_button" type="button" class="btn btn-info dungeonroute-add-to-collection"
                    data-publickey="{{ $dungeonroute->public_key }}"
                    aria-label="{{ __('view_common.maps.controls.view.add_to_collection_title') }}">
                <i class="fas fa-layer-group"></i>
                <span class="map_controls_element_label_toggle" style="display: none;">
                    {{ __('view_common.maps.controls.view.add_to_collection_title') }}
                </span>
            </button>
        </div>
    </div>

    {{-- The sidebar is fixed above Bootstrap's backdrop, so the dialog cannot live inside it --}}
    @section('scripts')
        @parent

        @include('common.collection.addtocollection', ['triggerSelector' => '.dungeonroute-add-to-collection'])
    @endsection
@endif
