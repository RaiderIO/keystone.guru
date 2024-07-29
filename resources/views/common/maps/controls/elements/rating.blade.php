<?php
/** @var \App\Models\DungeonRoute\Dungeonroute $dungeonroute */
$currentUserRating = $dungeonroute->getRatingByCurrentUser();
?>
    <!-- Rating -->
<div class="row no-gutters">
    <div class="col btn-group dropright">
        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
            <i class="{{ $currentUserRating !== null ? 'fas' : 'far' }} fa-star"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.rating.rate_this_route') }}
            </span>
        </button>
        <div id="map_rating_dropdown" class="dropdown-menu">
            @if( $dungeonroute->isOwnedByUser())
                <a class="dropdown-item disabled">
                    {{ __('view_common.maps.controls.elements.rating.unable_to_rate_own_route') }}
                </a>
            @else
                <a class="dropdown-item disabled">
                    {{ __('view_common.maps.controls.elements.rating.your_rating') }}
                </a>
                @for($i = 1; $i <= 10; $i++)
                    <a class="dropdown-item {{ $currentUserRating === $i ? 'active' : '' }}" data-rating="{{ $i }}">
                        {{ $i }}
                    </a>
                @endfor
            @endif
        </div>
    </div>
</div>
