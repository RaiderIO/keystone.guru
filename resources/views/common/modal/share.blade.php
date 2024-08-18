<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute|null $dungeonroute
 */


$show          ??= [];
$showLink      = $show['link'] ?? true;
$showEmbed     = $show['embed'] ?? true;
$showMdtExport = $show['mdt-export'] ?? true;
$showPublish   = $show['publish'] ?? true;

$shareLink      = route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]);
$shareLinkShort = route('dungeonroute.viewold', ['dungeonroute' => $dungeonroute]);
?>

@section('scripts')
    @parent

    <script type="text/javascript">

        let shareLink = '{{ $shareLink }}';
        let shareShortLink = '{{ $shareLinkShort }}';

        $(function () {
            $('#map_include_location_checkbox').on('change', function () {
                let includeLocation = $(this).is(':checked');

                let state = getState();
                let currentZoomLevel = state.getMapZoomLevel();
                /** @type {LatLng} */
                let center = getState().getDungeonMap().leafletMap.getCenter();

                // https://stackoverflow.com/a/12830454/771270
                let locationStr = includeLocation ? `?lat=${+center.lat.toFixed(2)}&lng=${+center.lng.toFixed(2)}&${+currentZoomLevel.toFixed(2)}` : '';

                $('#map_shareable_link').val(shareLink + locationStr);
                $('#map_shareable_short_link').val(shareShortLink + locationStr);
            });
        });
    </script>
@endsection

@include('common.general.inline', ['path' => 'common/dungeonroute/share'])

<!-- Shareable link -->
<h3 class="card-title">{{ __('view_common.modal.share.share') }}</h3>

@if($showPublish)
    <!-- Published state -->
    <div class="form-group">
        <label for="map_route_publish">
            {{ __('view_common.modal.share.publish') }}
        </label>
        <div class="row">
            <div id="map_route_publish_container" class="col"
                 style="display: block">
                @include('common.dungeonroute.publish', ['dungeonroute' => $dungeonroute])
            </div>
        </div>
        <div class="row">
            <div class="col">
                {!! sprintf(__('view_common.modal.share.review_route_settings'),
                    sprintf('<a href="#" data-toggle="modal" data-target="#edit_route_settings_modal">%s</a>', __('view_common.modal.share.route_settings'))
                ) !!}
            </div>
        </div>
    </div>
@endif
@if($showLink)
    <div class="form-group">
        <label for="map_shareable_link">
            {{ __('view_common.modal.share.link') }}
        </label>
        <div class="row">
            <div class="col input-group">
                {!! Form::text('map_shareable_link',
                    $shareLink,
                ['id' => 'map_shareable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                <div class="input-group-append">
                    <button id="map_shareable_link_copy_to_clipboard" class="btn btn-info"
                            data-toggle="tooltip"
                            title="{{ __('view_common.modal.share.copy_shareable_link_to_clipboard') }}">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="map_shareable_link">
            {{ __('view_common.modal.share.short_link') }}
        </label>
        <div class="row">
            <div class="col input-group">
                {!! Form::text('map_shareable_short_link', $shareLinkShort,
                ['id' => 'map_shareable_short_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                <div class="input-group-append">
                    <button id="map_shareable_short_link_copy_to_clipboard" class="btn btn-info"
                            data-toggle="tooltip"
                            title="{{ __('view_common.modal.share.copy_shareable_link_to_clipboard') }}">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-auto">
                {!! Form::checkbox('map_include_location', 1, 0, ['id' => 'map_include_location_checkbox', 'class' => 'form-control', 'style' => 'width: 23px; height: 23px;']) !!}
            </div>
            <div class="col pl-0">
                {!! Form::label('map_include_location', __('view_common.modal.share.include_location_in_link')) !!}
            </div>
        </div>
    </div>
@endif
@if($showEmbed)
    <div class="form-group">
        <label for="map_embedable_link">
            {{ __('view_common.modal.share.embed') }}
        </label>
        <div class="row">
            <div class="col input-group">
                {!! Form::text('map_embedable_link',
                sprintf('<iframe src="%s" style="width: 800px; height: 600px; border: none;"></iframe>',
                    route('dungeonroute.embed', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()])
                ),
                ['id' => 'map_embedable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                <div class="input-group-append">
                    <button id="map_embedable_link_copy_to_clipboard" class="btn btn-info"
                            data-toggle="tooltip"
                            title="{{ __('view_common.modal.share.copy_embed_code_to_clipboard') }}">
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
            {{ __('view_common.modal.share.mdt_string') }}
        </label>

        <div class="form-group">
            <div class="mdt_export_loader_container">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ __('view_common.modal.share.loading') }}</span>
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
                    <i class="far fa-copy"></i> {{ __('view_common.modal.share.copy_to_clipboard') }}
                </div>
            </div>
        </div>
        <div class="form-group">
            <div id="mdt_export_result_warnings">

            </div>
        </div>
    </div>
@endif
