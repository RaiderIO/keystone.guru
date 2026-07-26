<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The featured-creators row on the discover landing page.
 *
 * featuredCreators is supplied by FeaturedCreatorsComposer rather than by the controller, because
 * three different controller methods render the discover landing.
 *
 * @var Collection<int, User> $featuredCreators
 */

$featuredCreators ??= collect();
?>
@if($featuredCreators->isNotEmpty())
    <div class="discover_panel px-xl-2">
        <div class="row mt-4 align-items-center">
            <div class="col">
                <h2 class="h4 mb-0">
                    {{ __('view_creator.featured.title') }}
                </h2>
            </div>
            <div class="col-auto">
                <a href="{{ route('creators.index') }}">
                    {{ __('view_creator.featured.see_all') }} &raquo;
                </a>
            </div>
        </div>

        <div class="row g-3 mt-1 row-cols-2 row-cols-md-4 row-cols-xl-8">
            @foreach($featuredCreators as $creator)
                <div class="col">
                    @include('creator.card', ['creator' => $creator])
                </div>
            @endforeach
        </div>
    </div>
@endif
