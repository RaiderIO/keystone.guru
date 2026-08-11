<?php

/**
 * @var array{npc: int, spell: int, class: int} $stats
 */

$sections = [
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
        <div class="col-12 col-lg-8 text-center">
            <p class="lead">{{ __('view_compendium.index.intro') }}</p>
        </div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-12 col-lg-8">
            <div class="compendium_directory">
                @foreach($sections as $section)
                    <a href="{{ $section['route'] }}" class="compendium_directory_row">
                        <span class="compendium_directory_icon">
                            <i class="fas {{ $section['icon'] }}"></i>
                        </span>
                        <span>
                            <span class="compendium_directory_title">{{ $section['title'] }}</span>
                            <span class="compendium_directory_count">{{ $section['subtitle'] }}</span>
                            <p class="compendium_directory_desc">{{ $section['text'] }}</p>
                        </span>
                        <span class="compendium_directory_cta">
                            {{ $section['cta'] }} <i class="fas fa-arrow-right"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-12 col-lg-8">
            <div class="compendium_identity mb-0">
                <span class="compendium_directory_icon">
                    <i class="fas fa-bolt"></i>
                </span>
                <div class="compendium_identity_body">
                    <h5 class="compendium_identity_title">{{ __('view_compendium.index.data_source.title') }}</h5>
                    <p class="mb-0">{{ __('view_compendium.index.data_source.description') }}</p>
                </div>
                <div class="compendium_identity_actions">
                    <a href="https://raider.io/addon" target="_blank" rel="noopener"
                       class="btn btn-accent">
                        <i class="fas fa-download"></i> {{ __('view_compendium.index.data_source.cta') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-5 mb-4">
        <div class="col-12 col-lg-8">
            <h4 class="fw-bold mb-2">{{ __('view_compendium.index.how_it_works.title') }}</h4>
            @foreach($howItWorks as $step)
                <div class="compendium_record_section">
                    <div class="compendium_record_label">
                        <i class="fas {{ $step['icon'] }} me-1"></i>
                        {{ __(sprintf('view_compendium.index.how_it_works.%s.title', $step['key'])) }}
                    </div>
                    <div>
                        {{ __(sprintf('view_compendium.index.how_it_works.%s.description', $step['key'])) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
