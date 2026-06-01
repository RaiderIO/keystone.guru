<?php

use App\Models\CharacterClass;
use Illuminate\Support\Collection;

/**
 * @var Collection<CharacterClass> $characterClasses
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
            <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-4">
                <a href="{{ route('compendium.class.show', $characterClass) }}" class="text-decoration-none">
                    <div class="card h-100 text-center py-3 px-2">
                        <img src="{{ $characterClass->icon_url }}"
                             width="56" height="56"
                             alt="{{ __($characterClass->name) }}"
                             loading="lazy"
                             class="mx-auto mb-2 rounded"/>
                        <div class="font-weight-bold small">{{ __($characterClass->name) }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
