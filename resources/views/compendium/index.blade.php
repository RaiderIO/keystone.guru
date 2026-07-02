<?php

/**
 * @var array{npc: int, spell: int, class: int} $stats
 */

$cards = [
    [
        'icon'     => 'fa-dragon',
        'title'    => __('view_compendium.index.cards.npc.title'),
        'text'     => __('view_compendium.index.cards.npc.description'),
        'cta'      => __('view_compendium.index.cards.npc.cta'),
        'route'    => route('npc.compendium.index'),
        'subtitle' => sprintf('%s %s', number_format($stats['npc']), __('view_compendium.index.cards.npc.count_suffix')),
    ],
    [
        'icon'     => 'fa-magic',
        'title'    => __('view_compendium.index.cards.spell.title'),
        'text'     => __('view_compendium.index.cards.spell.description'),
        'cta'      => __('view_compendium.index.cards.spell.cta'),
        'route'    => route('spell.compendium.index'),
        'subtitle' => sprintf('%s %s', number_format($stats['spell']), __('view_compendium.index.cards.spell.count_suffix')),
    ],
    [
        'icon'     => 'fa-stream',
        'title'    => __('view_compendium.index.cards.activity.title'),
        'text'     => __('view_compendium.index.cards.activity.description'),
        'cta'      => __('view_compendium.index.cards.activity.cta'),
        'route'    => route('compendium.activity.index'),
        'subtitle' => __('view_compendium.index.cards.activity.subtitle'),
    ],
    [
        'icon'     => 'fa-hat-wizard',
        'title'    => __('view_compendium.index.cards.class.title'),
        'text'     => __('view_compendium.index.cards.class.description'),
        'cta'      => __('view_compendium.index.cards.class.cta'),
        'route'    => route('compendium.class.index'),
        'subtitle' => sprintf('%s %s', number_format($stats['class']), __('view_compendium.index.cards.class.count_suffix')),
    ],
];

$howItWorks = [
    ['icon' => 'fa-list', 'key' => 'step_1'],
    ['icon' => 'fa-search', 'key' => 'step_2'],
    ['icon' => 'fa-search-plus', 'key' => 'step_3'],
];
?>
@extends('layouts.sitepage', ['title' => __('view_compendium.index.title')])

@section('header-title')
    {{ __('view_compendium.index.header') }}
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 text-center">
            <p class="lead">{{ __('view_compendium.index.intro') }}</p>
        </div>
    </div>

    <div class="row mt-4">
        @foreach($cards as $card)
            <div class="col-12 col-md-6 col-lg-3 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <i class="fas {{ $card['icon'] }} fa-3x"></i>
                        </div>
                        <h5 class="card-title font-weight-bold">{{ $card['title'] }}</h5>
                        <div class="text-muted small mb-2">{{ $card['subtitle'] }}</div>
                        <p class="card-text flex-grow-1">{{ $card['text'] }}</p>
                        <a href="{{ $card['route'] }}" class="btn btn-primary mt-2">
                            {{ $card['cta'] }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-12 col-md">
                            <h5 class="font-weight-bold mb-2">
                                <i class="fas fa-bolt"></i> {{ __('view_compendium.index.data_source.title') }}
                            </h5>
                            <p class="mb-3 mb-md-0">{{ __('view_compendium.index.data_source.description') }}</p>
                        </div>
                        <div class="col-12 col-md-auto text-center">
                            <a href="https://raider.io/addon" target="_blank" rel="noopener"
                               class="btn btn-accent">
                                <i class="fas fa-download"></i> {{ __('view_compendium.index.data_source.cta') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-12 col-lg-10 text-center">
            <h4 class="font-weight-bold mb-4">{{ __('view_compendium.index.how_it_works.title') }}</h4>
            <div class="row">
                @foreach($howItWorks as $step)
                    <div class="col-12 col-md-4 mb-4 mb-md-0">
                        <div class="mb-2">
                            <i class="fas {{ $step['icon'] }} fa-2x"></i>
                        </div>
                        <h6 class="font-weight-bold">
                            {{ __(sprintf('view_compendium.index.how_it_works.%s.title', $step['key'])) }}
                        </h6>
                        <p class="text-muted">
                            {{ __(sprintf('view_compendium.index.how_it_works.%s.description', $step['key'])) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
