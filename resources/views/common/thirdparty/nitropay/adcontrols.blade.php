@if( isset($map) && $map )
    <div>
        <div id="nitropay-{{ $random }}-remove-ads" class="text-right nitropay-style float-left" style="display: none;">
            <a href="https://www.patreon.com/keystoneguru" target="_blank">
                {{ __('Remove ads') }}
            </a>
        </div>
        <div id="nitropay-{{ $random }}-report-ad" class="text-left float-right">
        </div>
    </div>
@else
    <div class="container">
        <div class="row">
            <div id="nitropay-{{ $random }}-remove-ads" class="col text-right nitropay-style" style="display: none;">
                <a href="https://www.patreon.com/keystoneguru" target="_blank">
                    {{ __('Remove ads') }}
                </a>
            </div>
            <div id="nitropay-{{ $random }}-report-ad" class="col text-left">
            </div>
        </div>
    </div>
@endif