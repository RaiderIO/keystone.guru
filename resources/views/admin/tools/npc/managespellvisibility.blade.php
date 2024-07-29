<?php

use App\Models\Npc\Npc;
use App\Models\Spell;
use Illuminate\Support\Collection;

/**
 * @var Collection<Npc>   $npcs
 * @var Collection<Spell> $spells
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.npc.managespellvisibility.title')])

@section('header-title', __('view_admin.tools.npc.managespellvisibility.header'))

@section('content')
    {{ $npcs->links() }}

    @foreach($npcs as $npc)
        <div class="form-element">
            <h4 class="form-label">
                <label for="npc-{{ $npc->id }}">{{ __($npc->name) }} ({{ __($npc->id) }})</label>
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
                                Spell not found
                            </div>
                        </div>
                    @else
                        <div class="col-auto">
                            <input type="checkbox" id="spell-{{ $npcSpell->spell_id }}"
                                   class="form-control left_checkbox"
                                   name="spell-{{ $npcSpell->spell_id }}"
                                   value="{{ $npcSpell->spell_id }}" {{ $spell->hidden ? 'checked' : '' }}>
                        </div>
                        <div class="col">
                            <div class="form-element" style="line-height: 2.5">
                                <a href="https://www.wowhead.com/spell={{$npcSpell->spell_id}}">
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
