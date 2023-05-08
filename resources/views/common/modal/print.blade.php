<?php
/** @var $dungeonroute App\Models\DungeonRoute|null */
?>

{{--@include('common.general.inline', [--}}
{{--    'path' => 'common/dungeonroute/simulate',--}}
{{--    'options' => [--}}
{{--        'dependencies' => ['common/maps/map'],--}}
{{--    ]--}}
{{--])--}}

<h3 class="card-title">{{ __('views/common.modal.print.title') }}</h3>

@component('common.general.alert', ['type' => 'info', 'name' => 'print-intro'])
    {{ __('views/common.modal.print.intro') }}
@endcomponent


<!-- General settings -->
<div class="form-group">
    <label for="simulate_key_level">
        {{ __('views/common.modal.print.floors_per_page') }}
        <i class="fas fa-info-circle" data-toggle="tooltip"
           title="{{ __('views/common.modal.print.floors_per_page_title') }}"></i>
    </label>
    <div class="row">
        <div class="col">
            {!! Form::select('floors_per_page', [2, 4], 2, ['id' => 'floors_per_page', 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
</div>


<div class="form-group row">
    <div class="col">
        <div id="simulate_get_string" class="btn btn-success">
            <i class="fas fa-file-pdf"></i> {{ __('views/common.modal.print.generate_printable_pdf') }}
        </div>
    </div>
</div>

<div class="form-group">

    <div class="form-group">
        <div class="simulationcraft_export_loader_container" style="display: none;">
            <div class="d-flex justify-content-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('views/common.modal.simulate.loading') }}</span>
                </div>
            </div>
        </div>
        <div class="simulationcraft_export_result_container" style="display: none;">
            <label for="simulationcraft_export_result">
                {{ __('views/common.modal.simulate.simulationcraft_string') }}
            </label>
            <textarea id="simulationcraft_export_result" class="w-100" style="height: 400px" readonly></textarea>
        </div>
    </div>
    <div class="form-group">
        <div class="simulationcraft_export_result_container" style="display: none;">
            <div class="btn btn-info copy_simulationcraft_string_to_clipboard w-100">
                <i class="far fa-copy"></i> {{ __('views/common.modal.simulate.copy_to_clipboard') }}
            </div>
        </div>
    </div>
</div>
