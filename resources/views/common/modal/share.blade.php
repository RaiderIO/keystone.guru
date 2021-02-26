<?php
$show = isset($show) ? $show : [];
$showLink = $show['link'] ?? true;
$showEmbed = $show['embed'] ?? true;
$showMdtExport = $show['mdt-export'] ?? true;
$showPublish = $show['publish'] ?? true;
?>
<!-- Shareable link -->
<h3 class="card-title">{{ __('Share') }}</h3>

@if($showPublish)
    <!-- Published state -->
    <div class="form-group">
        <label for="map_route_publish">
            {{ __('Publish') }}:
        </label>
        <div class="row">
            <div id="map_route_publish_container" class="col"
                 {{--                                 data-toggle="tooltip"--}}
                 {{--                                 title="{{ __('Kill enough enemy forces and kill all unskippable enemies to publish your route') }}"--}}
                 style="display: block">
                @include('common.dungeonroute.publish', ['dungeonroute' => $dungeonroute])
            </div>
        </div>
    </div>
@endif
@if($showLink)
    <div class="form-group">
        <label for="map_shareable_link">
            {{ __('Link') }}:
        </label>
        <div class="row">
            <div class="col input-group">
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
@if($showEmbed)
    <div class="form-group">
        <label for="map_embedable_link">
            {{ __('Embed') }}:
        </label>
        <div class="row">
            <div class="col input-group">
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
@if($showMdtExport)
    <div class="form-group">
        <label for="map_mdt_export">
            {{ __('MDT String') }}:
        </label>

        <div class="form-group">
            <div class="mdt_export_loader_container">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="mdt_export_result_container" style="display: none;">
                <textarea id="mdt_export_result" class="w-100" style="height: 400px" readonly></textarea>
            </div>
        </div>
        <div class="form-group">
            <div class="mdt_export_result_container" style="display: none;">
                <div class="btn btn-info copy_mdt_string_to_clipboard w-100">
                    <i class="far fa-copy"></i> {{ __('Copy to clipboard') }}
                </div>
            </div>
        </div>
        <div class="form-group">
            <div id="mdt_export_result_warnings">

            </div>
        </div>
    </div>
@endif