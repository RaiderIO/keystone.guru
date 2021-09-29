<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
$showNoAttributes = $showNoAttributes ?? false;
?>

<div class="form-group">
    @if($showNoAttributes)
        <label for="attributes" data-toggle="tooltip"
               title="{{ __('views/common.dungeonroute.attributes.no_attributes_title') }}">
            {{ __('views/common.dungeonroute.attributes.attributes') }}
        </label>
    @else
        <label for="attributes">{{ __('views/common.dungeonroute.attributes.attributes') }}</label>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
        __('views/common.dungeonroute.attributes.select_attributes_title')
         }}"></i>
    @endif
    <?php
    $allAttributes = \App\Models\RouteAttribute::all();
    $allAttributeCount = $allAttributes->count();
    /** @var \Illuminate\Support\Collection $attributes */
    $attributes = $allAttributes->groupBy('category');

    if ($showNoAttributes) {
        // Create a dummy attribute which users can tick on/off to include routes with no attributes.
        $noAttributes              = new \App\Models\RouteAttribute();
        $noAttributes->id          = -1;
        $noAttributes->name        = 'no-attributes';
        $noAttributes->description = 'No attributes';

        $attributes['meta'] = new \Illuminate\Support\Collection([$noAttributes]);
    }

    /** @var \Illuminate\Support\Collection $routeAttributes */
    $selectedIds = isset($selectedIds) ? $selectedIds : (!isset($dungeonroute) ? [] : $dungeonroute->routeattributes->pluck('id')->toArray());
    ?>
    <select multiple name="attributes[]" id="attributes" class="form-control selectpicker"
            size="{{ $allAttributeCount + $attributes->count() }}"
            data-selected-text-format="count > 1"
            data-count-selected-text="{{__('views/common.dungeonroute.attributes.attributes_selected')}}">
        @foreach ($attributes as $category => $categoryAttributes)
            <optgroup label="{{ ucfirst($category) }}">
                @foreach ($categoryAttributes as $attribute) {
                <option value="{{ $attribute->id }}"
                    {{ in_array($attribute->id, $selectedIds) ? 'selected' : '' }}>
                    {{ $attribute->description }}
                </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
