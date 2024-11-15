<?php

use App\Models\Team;

/**
 * @var Team $team
 */
?>
<div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
    <div class="form-group">
        <h4>
            {{ sprintf(__('view_team.edittabs.overview.title'), $team->name) }}
        </h4>
        @include('common.general.messages')

        <div class="row">
            <div class="col-lg mt-2">
                <div class="card text-center">
                    <div class="card-header">
                        {{ $team->name }}
                    </div>
                    @isset($team->iconfile)
                        <div class="card-body p-0">
                            <div class="row">
                                <div class="col" style="max-width: 128px">
                                    <img class="card-img-top d-block p-2"
                                         src="{{ $team->iconfile->getURL() }}"
                                         alt="{{ __('view_team.edit.icon_image_alt') }}"
                                         style="max-width: 128px; max-height: 128px;">
                                </div>
                                <div class="col text-left pl-0">
                                    {{ $team->description }}
                                </div>
                                <div class="col">
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card-body">
                            @isset($team->description)
                                {{ $team->description }}
                            @else
                                <h1>&nbsp;</h1>
                            @endisset
                        </div>
                    @endisset
                </div>
            </div>

            <div class="col-lg mt-2">
                <div class="card text-center">
                    <div class="card-header">
                        {{ __('view_team.edittabs.overview.routes') }}
                    </div>
                    <div class="card-body">
                        <h1>{{ $team->getVisibleRouteCount() }}</h1>
                    </div>
                </div>
            </div>

            <div class="col-lg mt-2">
                <div class="card text-center">
                    <div class="card-header">
                        {{ __('view_team.edittabs.overview.members') }}
                    </div>
                    <div class="card-body">
                        <h1>{{ $team->members()->count() }}</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
