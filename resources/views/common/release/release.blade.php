<?php
/** @var $release \App\Models\Release */
$showHeader = isset($showHeader) ? $showHeader : true;
?>
@if($showHeader)
    <h4>
        <a class="text-body" href="{{ route('release.view', ['release' => $release]) }}">
            {{ sprintf('%s (%s)', $release->version, $release->created_at->format('Y/m/d')) }}
        </a>
        @if(!isset($_COOKIE['changelog_release']) || (isset($_COOKIE['changelog_release']) && $_COOKIE['changelog_release'] < $release->id))
            <sup class="text-success">{{ __('NEW') }}</sup>
        @endif
    </h4>
@endif
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