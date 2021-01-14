<?php
$show                = isset($show) ? $show : [];
$showSharableLink    = isset($show['shareable-link']) ? $show['shareable-link'] : true;
$showEmbedableLink   = isset($show['embedable-link']) ? $show['embedable-link'] : true;
$showExportMdtString = isset($show['export-mdt-string']) ? $show['export-mdt-string'] : true;
?>
<!-- Shareable link -->
<div class="form-group">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ __('Share') }}</h5>
            @if($showSharableLink)
                <div class="form-group">
                    <div class="row">
                        <div class="input-group">
                            {!! Form::text('map_shareable_link', route('dungeonroute.view', ['dungeonroute' => $model->public_key]),
                            ['id' => 'map_shareable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                            <div class="input-group-append">
                                <button id="map_shareable_link_copy_to_clipboard" class="btn btn-info"
                                        data-toggle="tooltip" title="{{ __('Copy shareable link to clipboard') }}">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($showEmbedableLink)
                <div class="form-group">
                    <div class="row">
                        <div class="input-group">
                            {!! Form::text('map_embedable_link',
                            sprintf('<iframe src="%s" style="width: 800px; height: 600px; border: none;"></iframe>', route('dungeonroute.embed', ['dungeonroute' => $model])),
                            ['id' => 'map_embedable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                            <div class="input-group-append">
                                <button id="map_embedable_link_copy_to_clipboard" class="btn btn-info"
                                        data-toggle="tooltip" title="{{ __('Copy embed code to clipboard') }}">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($showExportMdtString)
                <div class="form-group">
                    <div class="row">
                        <div class="col">
                            <div class="input-group">
                                <button id="map_mdt_export" class="btn btn-info w-100" data-toggle="modal"
                                        data-target="#mdt_export_modal">
                                    <i class="fas fa-file-export"></i> {{ __('Export MDT string') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @isset($show['route-publish'])
            <!-- Published state -->
                <div class="form-group mb-0">
                    <div class="row">
                        <div id="map_route_publish_container" class="col"
                             {{--                                 data-toggle="tooltip"--}}
                             {{--                                 title="{{ __('Kill enough enemy forces and kill all unskippable enemies to publish your route') }}"--}}
                             style="display: block">
                            @include('common.dungeonroute.publish', ['model' => $model])
                        </div>
                    </div>
                </div>
            @endisset
        </div>
    </div>
</div>