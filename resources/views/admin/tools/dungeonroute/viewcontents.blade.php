<?php
use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute $dungeonroute
 */
?>
@extends('layouts.sitepage', [
    'showAds' => false,
    'title' => __('view_admin.tools.dungeonroute.viewcontents.title', ['dungeonRouteTitle' => $dungeonroute->title]),
    ])

@section('header-title', __('view_admin.tools.dungeonroute.viewcontents.header', ['dungeonRouteTitle' => $dungeonroute->title]))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('.admin-dungeonroute-copy-mdt').on('click', function () {
                const string = $(this).data('mdt-string');
                navigator.clipboard.writeText(string).then(function () {
                    showSuccessNotification(lang.get('js.copied_to_clipboard'));
                });
            });
        });
    </script>
@endsection

@section('content')
    {{-- Actions --}}
    <div class="card mb-4">
        <div class="card-header">
            {{ __('view_admin.tools.dungeonroute.viewcontents.section_actions') }}
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <a href="{{ route('admin.dungeonroute.edit', ['dungeonRoute' => $dungeonroute->id]) }}">
                    {{ __('view_admin.tools.dungeonroute.viewcontents.action_edit') }}
                </a>
                <small class="text-muted d-block">{{ __('view_admin.tools.dungeonroute.viewcontents.action_edit_description') }}</small>
            </li>
            <li class="list-group-item">
                @if($dungeonroute->mdtImport->isNotEmpty())
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ __('view_admin.tools.dungeonroute.viewcontents.action_mdt_import_string') }}</strong>
                            <small class="text-muted d-block">{{ __('view_admin.tools.dungeonroute.viewcontents.action_mdt_import_string_description') }}</small>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-secondary ml-3 admin-dungeonroute-copy-mdt"
                                data-mdt-string="{{ $dungeonroute->mdtImport->first()->import_string }}">
                            <i class="fas fa-copy"></i> {{ __('view_admin.tools.dungeonroute.viewcontents.action_copy') }}
                        </button>
                    </div>
                @else
                    <span class="text-muted">{{ __('view_admin.tools.dungeonroute.viewcontents.action_mdt_import_string_none') }}</span>
                @endif
            </li>
        </ul>
    </div>

    {{-- Debug data --}}
    <h5 class="mb-3">{{ __('view_admin.tools.dungeonroute.viewcontents.section_debug') }}</h5>

    <div id="dungeonrouteAccordion">
        <div class="card">
            <div class="card-header" id="headingDungeonroute">
                <h5 class="mb-0">
                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseDungeonroute"
                            aria-expanded="false"
                            aria-controls="collapseDungeonroute">
                        "{{ $dungeonroute->title }}"
                    </button>
                </h5>
            </div>

            <div id="collapseDungeonroute" class="collapse" aria-labelledby="headingDungeonroute"
                 data-parent="#dungeonrouteAccordion">
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
