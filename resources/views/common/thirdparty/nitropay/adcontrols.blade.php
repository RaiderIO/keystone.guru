@if( isset($map) && $map )
    <div style="height: 24px">
        <div id="{{ $id }}-remove-ads" class="text-end nitropay-style float-start" style="display: none;">
            <a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                {{ __('view_common.thirdparty.nitropay.adcontrols.remove_ads') }}
            </a>
        </div>
        <div id="{{ $id }}-report-ad" class="text-start float-end">
        </div>
    </div>
@else
    <div class="container">
        <div class="row">
            <div id="{{ $id }}-remove-ads" class="col text-end nitropay-style" style="display: none;">
                <a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                    {{ __('view_common.thirdparty.nitropay.adcontrols.remove_ads') }}
                </a>
            </div>
            <div id="{{ $id }}-report-ad" class="col text-start">
            </div>
        </div>
    </div>
@endif
