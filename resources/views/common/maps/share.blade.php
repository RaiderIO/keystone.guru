<!-- Shareable link -->
<div class="form-group">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ __('Share') }}</h5>
            <div class="row mb-2">
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
    </div>
</div>