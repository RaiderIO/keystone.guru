<?php

use App\Models\CharacterClass;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, CharacterClass> $characterClasses
 */
?>
@extends('layouts.sitepage', ['title' => __('view_compendium.class.index.title')])

@section('header-title')
    {{ __('view_compendium.class.index.header') }}
@endsection

@section('content')
    <div class="row">
        @foreach($characterClasses as $characterClass)
            <?php /** @var CharacterClass $characterClass */ ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                <a href="{{ route('compendium.class.show', $characterClass) }}" class="compendium_class_tile">
                    <img src="{{ $characterClass->icon_url }}"
                         width="48" height="48"
                         alt="{{ __($characterClass->name) }}"
                         loading="lazy"/>
                    <span>{{ __($characterClass->name) }}</span>
                </a>
            </div>
        @endforeach
    </div>
@endsection
