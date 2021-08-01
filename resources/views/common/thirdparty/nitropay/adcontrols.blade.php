@if( isset($map) && $map )
    <div style="height: 24px">
        <div id="{{ $id }}-remove-ads" class="text-right nitropay-style float-left" style="display: none;">
            <a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                {{ __('Remove ads') }}
            </a>
        </div>
        <div id="{{ $id }}-report-ad" class="text-left float-right">
        </div>
    </div>
@else
    <div class="container">
        <div class="row">
            <div id="{{ $id }}-remove-ads" class="col text-right nitropay-style" style="display: none;">
                <a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                    {{ __('Remove ads') }}
                </a>
            </div>
            <div id="{{ $id }}-report-ad" class="col text-left">
            </div>
        </div>
    </div>
@endif