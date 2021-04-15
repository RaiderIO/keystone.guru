<!-- Mouseover enemy information -->
<footer id="enemy_info_container" class="fixed-bottom map_fade_out" style="display: none;">
    <div class="card">
        <div class="card-body p-3">
            <div class="row">
                <div class="col">
                    <h5 class="card-title">{{ __('Enemy info') }}</h5>
                </div>
                <div class="col-auto">
                    <a href="#" data-toggle="modal"
                       data-target="#userreport_enemy_modal">
                        <button class="btn btn-warning btn-sm w-100" data-toggle="tooltip" title="{{ __('Report an issue') }}">
                            <i class="fa fa-bug"></i>
                        </button>
                    </a>
                </div>
            </div>
            <div id="enemy_info_key_value_container">

            </div>
        </div>
    </div>
</footer>