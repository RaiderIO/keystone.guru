<?php
/**
 * @var DungeonRoute               $dungeonroute
 * @var Collection<RouteAttribute> $allRouteAttributes
 **/

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\RouteAttribute;
use Illuminate\Support\Collection;

$showNoAttributes ??= false;
?>

<div class="form-group">
    @if($showNoAttributes)
        <label for="attributes" data-toggle="tooltip"
               title="{{ __('view_common.dungeonroute.attributes.no_attributes_title') }}">
            {{ __('view_common.dungeonroute.attributes.attributes') }}
        </label>
    @else
        <label for="attributes">{{ __('view_common.dungeonroute.attributes.attributes') }}</label>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
        __('view_common.dungeonroute.attributes.select_attributes_title')
         }}"></i>
    @endif
    <?php
    $allRouteAttributeCount = $allRouteAttributes->count();
    /** @var Collection<RouteAttribute> $routeAttributes */
    $routeAttributes = $allRouteAttributes->groupBy('category');

    if ($showNoAttributes) {
        $routeAttributes['meta'] = collect([
            // Create a dummy attribute which users can tick on/off to include routes with no attributes.
            new RouteAttribute([
                'id'   => -1,
                'key'  => 'no_attributes',
                'name' => 'routeattributes.no_attributes',
            ]),
        ]);
    }

    /** @var Collection $routeAttributes */
    $selectedIds ??= !isset($dungeonroute) ? [] : $dungeonroute->routeattributes->pluck('id')->toArray();
    ?>
    <select multiple name="attributes[]" id="attributes" class="form-control selectpicker"
            size="{{ $allRouteAttributeCount + $routeAttributes->count() }}"
            data-selected-text-format="count > 1"
            data-count-selected-text="{{__('view_common.dungeonroute.attributes.attributes_selected')}}">
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
