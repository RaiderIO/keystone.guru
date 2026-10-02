<div class="row g-0">
    <div class="col">
        <input type="checkbox" class="btn-check" value="1" autocomplete="off"
               id="map_enemy_visuals_map_mdt_clones_to_enemies"
               name="map_enemy_visuals_map_mdt_clones_to_enemies"
               aria-label="{{ __('view_common.maps.controls.elements.mdtclones.mdt') }}"/>
        <label class="btn btn-info" for="map_enemy_visuals_map_mdt_clones_to_enemies"
               data-bs-toggle="tooltip" data-bs-placement="right"
               data-bs-title="{{ __('view_common.maps.controls.elements.mdtclones.mdt') }}">
            <i class="fas fa-clone"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.mdtclones.mdt') }}
            </span>
        </label>
    </div>
</div>
<div class="row g-0">
    <div class="col">
        <button type="button" id="map_enemy_visuals_mdt_auto_solve" class="btn btn-info"
                aria-label="{{ __('view_common.maps.controls.elements.mdtclones.auto_solve') }}"
                data-bs-toggle="tooltip" data-bs-placement="right"
                data-bs-title="{{ __('view_common.maps.controls.elements.mdtclones.auto_solve') }}">
            <i class="fas fa-puzzle-piece"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.mdtclones.auto_solve') }}
            </span>
        </button>
    </div>
</div>
