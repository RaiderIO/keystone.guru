<?php
/** @var $dungeonroute \App\Models\DungeonRoute\DungeonRoute */
/** @var $allRouteAttributes \Illuminate\Support\Collection|\App\Models\RouteAttribute[] */

$showNoAttributes ??= false;
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
    $allRouteAttributeCount = $allRouteAttributes->count();
    /** @var \Illuminate\Support\Collection $routeAttributes */
    $routeAttributes = $allRouteAttributes->groupBy('category');

    if ($showNoAttributes) {
        $routeAttributes['meta'] = collect([
            // Create a dummy attribute which users can tick on/off to include routes with no attributes.
            new \App\Models\RouteAttribute([
                'id'   => -1,
                'key'  => 'no_attributes',
                'name' => 'routeattributes.no_attributes',
            ])
        ]);
    }

    /** @var \Illuminate\Support\Collection $routeAttributes */
    $selectedIds ??= !isset($dungeonroute) ? [] : $dungeonroute->routeattributes->pluck('id')->toArray();
    ?>
    <select multiple name="attributes[]" id="attributes" class="form-control selectpicker"
            size="{{ $allRouteAttributeCount + $routeAttributes->count() }}"
            data-selected-text-format="count > 1"
            data-count-selected-text="{{__('views/common.dungeonroute.attributes.attributes_selected')}}">
        @foreach ($routeAttributes as $category => $categoryAttributes)
            <optgroup label="{{ ucfirst($category) }}">
                @foreach ($categoryAttributes as $attribute)
                    <option value="{{ $attribute->id }}"
                        {{ in_array($attribute->id, $selectedIds) ? 'selected' : '' }}>
                        {{ __($attribute->name) }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
