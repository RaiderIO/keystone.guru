<?php

use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

// map_killzonessidebar_killzone_description_modal_supported_html_tags
/** @var Collection<string, Collection<int, Spell>> $spellsSelect */

$spellsSelect = $spellsSelect->mapWithKeys(static fn(Collection $spells, string $categoryName) => [
    __($categoryName) => $spells->mapWithKeys(
        static fn(Spell $spell) => [
            $spell->id => [
                'icon_url' => $spell['icon_url'],
                'name'     => __($spell['name']),
            ]
        ]
    )
])->toArray();
?>
<div id="pull_sidebar_workbench" class="pull_workbench p-2" style="display: none;">
    <div class="row g-0 pull_workbench_row pull_workbench_header">
        <div class="col">
            <h5 id="pull_sidebar_workbench_header" class="text-center mt-1">

            </h5>
        </div>
    </div>

    <div class="row g-0 pull_workbench_row">
        <div class="col">
            <div data-bs-toggle="tooltip"
                 title="{{ __('view_common.maps.controls.pullsworkbench.description') }}">
                <button id="map_killzonessidebar_killzone_description" class="btn btn-primary"
                        data-bs-toggle="modal" data-bs-target="#map_killzonessidebar_killzone_description_modal">
                    <i class="fas fa-font"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-0 pull_workbench_row">
        <div class="col">
            <div data-bs-toggle="tooltip"
                 title="{{ __('view_common.maps.controls.pullsworkbench.spells') }}">
                <button id="map_killzonessidebar_killzone_spells" class="btn btn-primary"
                        data-bs-toggle="modal" data-bs-target="#map_killzonessidebar_killzone_spells_modal">
                    <i class="fas fa-magic"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-0 pull_workbench_row">
        <div class="col">
            <div id="map_killzonessidebar_killzone_kill_area_label"
                 data-bs-toggle="tooltip" title="">
                <button id="map_killzonessidebar_killzone_has_killzone"
                        class="btn btn-primary" data-bs-toggle="button" aria-pressed="false">
                    <i class="fas fa-bullseye"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-0 pull_workbench_row">
        <div class="col">
            <button id="map_killzonessidebar_killzone_color"
                    class="btn map_killzonessidebar_color_btn w-100">

            </button>
        </div>
    </div>

    <div class="row g-0 pull_workbench_row">
        <div class="col">
            <div data-bs-toggle="tooltip"
                 title="{{ __('view_common.maps.controls.pullsworkbench.delete_killzone') }}">
                <button id="map_killzonessidebar_killzone_delete" class="btn btn-danger">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>


@section('content')
    @parent

    @component('common.general.modal', ['id' => 'map_killzonessidebar_killzone_description_modal'])
        <div class="form-group">
            <h4>
                {{ html()->label(__('view_common.maps.controls.pullsworkbench.modal.description.label'), 'map_killzonessidebar_killzone_description_modal_textarea')->id('map_killzonessidebar_killzone_description_modal_label') }}
            </h4>
            <div id="map_killzonessidebar_killzone_description_modal_supported_html_tags" class="form-group">
            </div>
            <div class="form-group">
                {{ __('view_common.maps.controls.pullsworkbench.modal.supported_domains') }}
                <span id="map_killzonessidebar_killzone_description_modal_supported_domains" class="fas fa-info-circle"
                      data-bs-toggle="tooltip" data-bs-html="true">

                </span>
            </div>
            <div id="map_killzonessidebar_killzone_description_modal_remaining_characters"
                 class="form-group text-warning" style="display: none;">
            </div>
            {{ html()->textarea('map_killzonessidebar_killzone_description_modal_textarea', '')->class('form-control')->id('map_killzonessidebar_killzone_description_modal_textarea') }}
        </div>
        <div class="form-group">
            <div id="map_killzonessidebar_killzone_description_modal_save" class="btn btn-primary" data-bs-dismiss="modal">
                {{ __('view_common.maps.controls.pullsworkbench.modal.description.save') }}
            </div>
        </div>
    @endcomponent

    @component('common.general.modal', ['id' => 'map_killzonessidebar_killzone_spells_modal'])
        <div class="form-group">
            {!! html()->label(
                __('view_common.maps.controls.pullsworkbench.modal.spells.label'),
                'map_killzonessidebar_killzone_spells_modal_select',
            ) !!}
            @include('common.forms.select.imageselectcategories', [
                'id' => 'map_killzonessidebar_killzone_spells_modal_select',
                'valuesByCategory' => $spellsSelect,
                'multiple' => true,
                'liveSearch' => true,
            ])
        </div>
        <div class="form-group">
            <div id="map_killzonessidebar_killzone_spells_modal_save" class="btn btn-primary" data-bs-dismiss="modal">
                {{ __('view_common.maps.controls.pullsworkbench.modal.spells.save') }}
            </div>
        </div>
    @endcomponent
@endsection
