<?php

use App\Models\Dungeon;
use App\Models\Npc\Npc;
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

            $('.change_game_version').bind('click', function () {
                if ($(this).hasClass('disabled')) {
                    return;
                }

                let spellId = $(this).data('spell-id');
                let gameVersionId = $(this).data('game-version-id');

                $.ajax({
                    type: 'PUT',
                    url: `/ajax/admin/spell/${spellId}`,
                    data: {
                        game_version_id: gameVersionId
                    },
                    dataType: 'json',
                    success: function (data) {
                        showSuccessNotification(lang.get('messages.change_spell_game_version_success'));

                        $(`.change_game_version-${spellId}`).addClass('disabled');
                        $(`.spell_wowhead_url-${spellId}`).attr('href', data.wowhead_url);
                    },
                    error: function () {
                        showErrorNotification(lang.get('messages.change_spell_game_version_error'));
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
            @foreach($npc->npcSpells as $npcSpell)
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
                        @if($dungeon !== null)
                            <div class="col-2">
                                    <?php
                                    $canChangeGameVersion = $spell->gameVersion->id !== $dungeon->gameVersion->id;
                                    ?>
                                <button type="button"
                                        class="btn btn-warning btn-sm change_game_version change_game_version-{{ $spell->id }}"
                                        {{ $canChangeGameVersion ? '' : 'disabled' }}
                                        data-spell-id="{{ $spell->id }}"
                                        data-game-version-id="{{ $dungeon->gameVersion->id }}"
                                >
                                    @if($canChangeGameVersion)
                                        {{ __($spell->gameVersion->name) }} -> {{ __($dungeon->gameVersion->name) }}
                                    @else
                                        {{ __($spell->gameVersion->name) }}
                                    @endif
                                </button>
                            </div>
                        @endif
                        <div class="col">
                            <div class="form-element" style="line-height: 2.5">
                                <a class="spell_wowhead_url-{{ $spell->id }}"
                                    href="{{ Spell::getWowheadLink($spell->game_version_id, $spell->id, $spell->name) }}"
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
