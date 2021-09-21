<?php
/** @var $dungeonroute App\Models\DungeonRoute|null */

$show          = $show ?? [];
$showLink      = $show['link'] ?? true;
$showEmbed     = $show['embed'] ?? true;
$showMdtExport = $show['mdt-export'] ?? true;
$showPublish   = $show['publish'] ?? true;
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/share'])

<!-- Shareable link -->
<h3 class="card-title">{{ __('views/common.modal.share.share') }}</h3>

@if($showPublish)
    <!-- Published state -->
    <div class="form-group">
        <label for="map_route_publish">
            {{ __('views/common.modal.share.publish') }}
        </label>
        <div class="row">
            <div id="map_route_publish_container" class="col"
                 style="display: block">
                @include('common.dungeonroute.publish', ['dungeonroute' => $dungeonroute])
            </div>
        </div>
        <div class="row">
            <div class="col">
                {!! sprintf(__('views/common.modal.share.review_route_settings'),
                    sprintf('<a href="#" data-toggle="modal" data-target="#route_settings_modal">%s</a>', __('views/common.modal.share.route_settings'))
                ) !!}
            </div>
        </div>
    </div>
@endif
@if($showLink)
    <div class="form-group">
        <label for="map_shareable_link">
            {{ __('views/common.modal.share.link') }}
        </label>
        <div class="row">
            <div class="col input-group">
                {!! Form::text('map_shareable_link', route('dungeonroute.view', ['dungeonroute' => $dungeonroute]),
                ['id' => 'map_shareable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                <div class="input-group-append">
                    <button id="map_shareable_link_copy_to_clipboard" class="btn btn-info"
                            data-toggle="tooltip"
                            title="{{ __('views/common.modal.share.copy_shareable_link_to_clipboard') }}">
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
            {{ __('views/common.modal.share.embed') }}
        </label>
        <div class="row">
            <div class="col input-group">
                {!! Form::text('map_embedable_link',
                sprintf('<iframe src="%s" style="width: 800px; height: 600px; border: none;"></iframe>', route('dungeonroute.embed', ['dungeonroute' => $dungeonroute])),
                ['id' => 'map_embedable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                <div class="input-group-append">
                    <button id="map_embedable_link_copy_to_clipboard" class="btn btn-info"
                            data-toggle="tooltip"
                            title="{{ __('views/common.modal.share.copy_embed_code_to_clipboard') }}">
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
            {{ __('views/common.modal.share.mdt_string') }}
        </label>

        <div class="form-group">
            <div class="mdt_export_loader_container">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ __('views/common.modal.share.loading') }}</span>
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
                    <i class="far fa-copy"></i> {{ __('views/common.modal.share.copy_to_clipboard') }}
                </div>
            </div>
        </div>
        <div class="form-group">
            <div id="mdt_export_result_warnings">

            </div>
        </div>
    </div>
@endif
