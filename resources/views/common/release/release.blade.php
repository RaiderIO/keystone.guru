<?php

use App\Models\Release;
use App\Models\ReleaseChangelogCategory;
use App\Models\ReleaseChangelogChange;
use Illuminate\Support\Collection;

/**
 * @var Collection<ReleaseChangelogCategory> $categories
 * @var Release                              $release
 */

$showHeader ??= true;
?>
<div class="form-group">
    @if($showHeader)
        <div class="row no-gutters">
            <div class="col">
                <h4>
                    <a class="text-body" href="{{ route('release.view', ['release' => $release]) }}">
                        {{ sprintf('%s (%s)', $release->version, $release->created_at->format('Y/m/d')) }}
                    </a>
                    @if(!isset($_COOKIE['changelog_release']) || (isset($_COOKIE['changelog_release']) && $_COOKIE['changelog_release'] < $release->id))
                        <sup class="text-success">{{ __('view_common.release.release.new') }}</sup>
                    @endif
                </h4>
            </div>
        </div>
    @endif
    @if($release->changelog->description !== null)
        <div class="row">
            <div class="col">
                <p>
                    {{ $release->changelog->description }}
                </p>
            </div>
        </div>
    @endisset
    <?php
    /** @var ReleaseChangelogCategory $category */
    ?>
    @foreach ($release->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $groupedChange)
        <p>
                <?php /** @var ReleaseChangelogChange $change */ ?>
            {{ __($categories->where('id', $categoryId)->first()->name) }}:
        </p>
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
    @endforeach
</div>
