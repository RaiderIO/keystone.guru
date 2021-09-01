<?php
/** @var \App\Models\DungeonRoute $dungeonroute */
?>
@extends('layouts.sitepage', [
    'showAds' => false,
    'title' => __('views/admin.tools.dungeonroute.viewcontents.title', ['dungeonRouteTitle' => $dungeonroute->title]),
    ])

@section('header-title', __('views/admin.tools.dungeonroute.viewcontents.header', ['dungeonRouteTitle' => $dungeonroute->title]))

@section('content')
    <div id="dungeonrouteAccordion">
        <div class="card">
            <div class="card-header" id="headingDungeonroute">
                <h5 class="mb-0">
                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseDungeonroute"
                            aria-expanded="false"
                            aria-controls="collapseDungeonroute">
                        {{ $dungeonroute->title }}
                    </button>
                </h5>
            </div>

            <div id="collapseDungeonroute" class="collapse" aria-labelledby="headingDungeonroute" data-parent="#dungeonrouteAccordion">
                <div class="card-body">
                    @dump($dungeonroute->withoutRelations())
                </div>
            </div>
        </div>

        @foreach($dungeonroute->getRelations() as $name => $value)
            <div class="card">
                <div class="card-header" id="heading{{ $name }}">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse{{ $name }}"
                                aria-expanded="false"
                                aria-controls="collapse{{ $name }}">
                            {{ ucfirst($name) }}
                        </button>
                    </h5>
                </div>

                <div id="collapse{{ $name }}" class="collapse" aria-labelledby="heading{{ $name }}"
                     data-parent="#dungeonrouteAccordion">
                    <div class="card-body">
                        @dump($value)
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection