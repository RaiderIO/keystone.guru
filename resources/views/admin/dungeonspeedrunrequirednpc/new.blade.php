<?php
use App\Models\Dungeon;
use App\Models\Floor\Floor;

/**
 * @var Dungeon              $dungeon
 * @var Floor                $floor
 * @var array<int, int>      $npcIds
 * @var array<int, int|null> $npcIdsWithNullable
 * @var int                  $difficulty
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon, $floor, $difficulty],
    'showAds' => false,
    'title' => sprintf(
        __('view_admin.dungeonspeedrunrequirednpc.new.title'),
        Dungeon::getDifficultyName($difficulty)
    )]
)
@section('header-title')
    {{ __('view_admin.dungeonspeedrunrequirednpc.new.header', [
        'difficulty' => Dungeon::getDifficultyName($difficulty),
        'dungeon'    => __($dungeon->name),
    ]) }}
@endsection

@section('content')
    {{ html()->form('POST', route('admin.dungeonspeedrunrequirednpc.savenew', ['dungeon' => $dungeon, 'floor' => $floor, 'difficulty' => $difficulty]))->open() }}

    {{ html()->hidden('dungeon_id', $dungeon->id) }}
    {{ html()->hidden('floor_id', $floor->id) }}
    {{ html()->hidden('difficulty', $difficulty) }}

    <div class="mb-3{{ $errors->has('npc_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.dungeonspeedrunrequirednpc.new.npc_id'), 'npc_id')->class('fw-bold') }}
        <span class="form-required">*</span>
        {{ html()->select('npc_id', $npcIds)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc_id'])
    </div>

    <div class="mb-3{{ $errors->has('npc2_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.dungeonspeedrunrequirednpc.new.linked_npc_ids'), 'npc2_id')->class('fw-bold') }}
        {{ html()->select('npc2_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc2_id'])
    </div>

    <div class="mb-3{{ $errors->has('npc3_id') ? ' has-error' : '' }}">
        {{ html()->select('npc3_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc3_id'])
    </div>

    <div class="mb-3{{ $errors->has('npc4_id') ? ' has-error' : '' }}">
        {{ html()->select('npc4_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc4_id'])
    </div>

    <div class="mb-3{{ $errors->has('npc5_id') ? ' has-error' : '' }}">
        {{ html()->select('npc5_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc5_id'])
    </div>

    <div class="mb-3{{ $errors->has('count') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.dungeonspeedrunrequirednpc.new.count'), 'count')->class('fw-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('count', 0)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'count'])
    </div>

    {{ html()->input('submit')->value(__('view_admin.dungeonspeedrunrequirednpc.new.submit'))->class('btn btn-info') }}

    {{ html()->form()->close() }}
@endsection
