<?php

use App\Models\Npc\Npc;
use Illuminate\Support\Collection;

/**
 * @var string                                   $id
 * @var string|null                              $name
 * @var string|false                             $label
 * @var Collection<int, Npc>                     $npcs        Flat list (mutually exclusive with $npcsByGroup)
 * @var Collection<string, Collection<int, Npc>> $npcsByGroup Grouped (mutually exclusive with $npcs)
 * @var mixed|null                               $selected
 * @var bool                                     $multiple
 * @var bool                                     $required
 * @var bool                                     $liveSearch
 * @var bool                                     $showId      Show NPC ID alongside name as "<name> (<id>)"
 */

$id         ??= 'npc_id_select';
$name       ??= null;
$label      ??= __('view_common.npc.select.npc');
$selected   ??= null;
$multiple   ??= false;
$required   ??= false;
$liveSearch ??= true;
$showId     ??= false;

if (!isset($npcsByGroup)) {
    $npcsByGroup = collect(['' => $npcs ?? collect()]);
}
?>
<div class="form-group">
    @if($label !== false)
        {{ html()->label($label . ($required ? '<span class="form-required">*</span>' : ''), $id) }}
    @endif
    <select id="{{ $id }}"
            @isset($name) name="{{ $name }}" @endisset
            class="form-control selectpicker"
            @if($liveSearch) data-live-search="true" @endif
            @if($multiple) multiple @endif>
        @foreach($npcsByGroup as $group => $groupNpcs)
            @if($group)
                <optgroup label="{{ $group }}">
            @endif
            @foreach($groupNpcs as $npc)
                <?php ob_start() ?>

                @include('common.forms.select.imageoption', [
                    'url'  => ksgAsset($npc->enemy_portrait_url),
                    'name' => $showId ? sprintf('%s (%d)', __($npc->name), $npc->id) : __($npc->name),
                ])

                <?php $html = ob_get_clean(); ?>
                <option value="{{ $npc->id }}"
                        @if($selected !== null && $selected == $npc->id) selected="selected" @endif
                        data-content="{{{$html}}}">{{ $showId ? sprintf('%s (%d)', __($npc->name), $npc->id) : __($npc->name) }}</option>
            @endforeach
            @if($group)
                </optgroup>
            @endif
        @endforeach
    </select>
</div>
