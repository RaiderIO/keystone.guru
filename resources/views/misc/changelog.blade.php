<?php
/** @var $releases \Illuminate\Support\Collection */
/** @var $categories \Illuminate\Support\Collection */
?>
@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Changelog')])

@section('header-title', __('Changelog'))

@section('content')
    @foreach($releases as $release)
        <?php
        /** @var $release \App\Models\Release */
        ?>
        <h4>
            {{ sprintf('%s (%s)', $release->version, $release->created_at->format('Y/m/d')) }}
        </h4>
        <?php
        /** @var \App\Models\ReleaseChangelogCategory $category */
        ?>
        @foreach ($release->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $groupedChange)
            <p>
                <?php /** @var $change \App\Models\ReleaseChangelogChange */?>
                {{ \App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->category }}:
            <ul>
                @foreach ($groupedChange as $category => $change)
                    <li>
                        @if($change->ticket_id !== null)
                            <a href="https://github.com/Wotuu/keystone.guru/issues/{{ $change->ticket_id }}">#{{ $change->ticket_id }}</a>
                        @endif
                        {!! $change->change !!}
                    </li>
                @endforeach
            </ul>
            </p>
        @endforeach
    @endforeach

    {{ $releases->links() }}

@endsection