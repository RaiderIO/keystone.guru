<!-- Mouseover enemy information -->
<footer id="enemy_info_container" class="fixed-bottom" style="display: none;">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ __('Enemy info') }}</h5>
            <div id="enemy_info_key_value_container">

            </div>
            <div class="row mt-2">
                <div class="col">
                    <a href="#" data-toggle="modal"
                       data-target="#userreport_enemy_modal">
                        <button class="btn btn-warning w-100">
                            <i class="fa fa-bug"></i>
                            {{ __('Report an issue') }}
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>