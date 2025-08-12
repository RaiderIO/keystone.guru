<?php

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Collection<Npc>   $npcs
 * @var Collection<Spell> $spells
 * @var Dungeon|null      $dungeon
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.npc.managespellvisibility.title')])

@section('header-title', __('view_admin.tools.npc.managespellvisibility.header'))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {

            // All input fields that are checkboxes with spell class
            $('.spell').bind('change', function () {
                let hiddenOnMap = $(this).is(':checked');
                let spellId = $(this).data('id');
                $.ajax({
                    type: 'PUT',
                    url: `/ajax/admin/spell/${spellId}`,
                    data: {
                        hidden_on_map: hiddenOnMap ? 0 : 1
                    },
                    dataType: 'json',
                    success: function () {
                        showSuccessNotification(lang.get('messages.toggle_spell_visibility_success'));

                        $(`.spell-${spellId}`).prop('checked', hiddenOnMap);
                    },
                    error: function () {
                        showErrorNotification(lang.get('messages.toggle_spell_visibility_error'));
                    }
                });
            });
        });
    </script>
@endsection

@section('content')

    {{ Form::open(['route' => ['admin.tools.npc.managespellvisibility.submit']]) }}
    @include('common.dungeon.select', [
        'id' => 'spell_visibility_dungeon_select',
        'activeOnly' => false,
        'ignoreGameVersion' => true,
        'selected' => isset($dungeon) ? optional($dungeon)->id : null,
    ])

    <div class="form-group">
        <button type="submit" class="btn btn-success">
            {{ __('view_admin.tools.npc.managespellvisibility.submit') }}
        </button>
    </div>

    {{ Form::close() }}

    {{ $npcs->links() }}

    @foreach($npcs as $npc)
        <div class="form-group">
            <h4>
                {{ __($npc->name) }} ({{ __($npc->id) }})
            </h4>
            <?php
                $sortedNpcSpells = $npc->npcSpells->sortBy(function(NpcSpell $npcSpell) use($spells) {
                    /** @var Spell $spell */
                    $spell = $spells->get($npcSpell->spell_id);
                    return __($spell?->name) ?? '';
                })->values();
                ?>
            @foreach($sortedNpcSpells as $npcSpell)
                    <?php
                    /** @var Spell $spell */
                    $spell = $spells->get($npcSpell->spell_id);
                    ?>
                <div class="row">
                    @if($spell === null)
                        <div class="col">
                            <div class="form-element">
                                {{ __('view_admin.tools.npc.managespellvisibility.spell_not_found') }}
                                ({{ $npcSpell->spell_id }})
                            </div>
                        </div>
                    @else
                        <div class="col-auto">
                            <input type="checkbox"
                                   class="form-control left_checkbox spell spell-{{ $npcSpell->spell_id }}"
                                   name="spell-{{ $npcSpell->spell_id }}"
                                   data-id="{{ $npcSpell->spell_id }}"
                                   value="{{ $npcSpell->spell_id }}" {{ $spell->hidden_on_map ? '' : 'checked' }}>
                        </div>
                        <div class="col">
                            <div class="form-element" style="line-height: 2.5">
                                <a class="spell_wowhead_url-{{ $spell->id }}"
                                   href="{{ Spell::getWowheadLink($spell->game_version_id, $spell->id, __($spell->name, [], 'en_US')) }}"
                                   data-wh-icon-size="medium"
                                >
                                    <img src="{{$spell->icon_url}}" width="32px" alt="{{ __($spell->name) }}"/>
                                    {{ __($spell->name) }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    {{ $npcs->links() }}
@endsection
