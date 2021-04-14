<?php
/** @var \App\Models\Dungeonroute $dungeonroute */
$currentUserRating = $dungeonroute->getRatingByCurrentUser();
?>
<!-- Rating -->
<div class="row no-gutters">

    <div class="col" data-toggle="tooltip" data-placement="right" title="{{ __('Rate this route') }}">
        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
            <i class="{{ $currentUserRating !== false ? 'fas' : 'far' }} fa-star"></i>
        </button>
        <div id="map_rating_dropdown" class="dropdown-menu">
            @if( $dungeonroute->isOwnedByUser())
                <a class="dropdown-item disabled">
                    {{ __('You cannot rate your own route') }}
                </a>
            @else
                <a class="dropdown-item disabled">
                    {{ __('Your rating') }}
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