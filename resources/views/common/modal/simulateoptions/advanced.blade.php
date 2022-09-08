<?php
$hasAdvancedSimulation = Auth::check() && Auth::user()->hasPatreonBenefit(\App\Models\Patreon\PatreonBenefit::ADVANCED_SIMULATION);
?>
<div class="form-group">
    <div id="simulate_route">
        <div class="card">
            <div class="card-header" id="simulate_route_advanced_heading">
                <h5 class="mb-0">
                    <a href="#" class="btn btn-link" data-toggle="collapse"
                       data-target="#simulate_route_advanced_collapse"
                       aria-expanded="false" aria-controls="simulate_route_advanced_collapse">
                        {{ __('views/common.modal.simulateoptions.advanced.advanced_options') }}
                    </a>
                </h5>
            </div>

            <div id="simulate_route_advanced_collapse" class="collapse"
                 aria-labelledby="simulate_route_advanced_heading"
                 data-parent="#simulate_route">
                <div class="card-body">
                    @component('common.general.alert', ['type' => 'info', 'name' => 'simulateoptions-advanced-patreon-only'])
                        {!! __('views/common.modal.simulateoptions.advanced.patreon_only', ['patreon' =>
                                     sprintf('<a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">%s</a>', __('views/common.modal.simulateoptions.advanced.patreon_link_text'))
                                     ]) !!}
                    @endcomponent

                    Content!
                </div>
            </div>
        </div>
    </div>
</div>
