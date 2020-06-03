<?php
/** @var $release \App\Models\Release */
$showHeader = isset($showHeader) ? $showHeader : true;
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
                    <sup class="text-success">{{ __('NEW') }}</sup>
                @endif
            </h4>
        </div>
        @if(Auth::check() && Auth::getUser()->hasRole('admin'))
            <div class="col-auto cursor-pointer text-primary copy_release_format_reddit" data-id="{{$release->id}}">
                <i class="fab fa-reddit"></i>
            </div>
            <div class="col-auto cursor-pointer text-primary copy_release_format_discord ml-1" data-id="{{$release->id}}">
                <i class="fab fa-discord"></i>
            </div>
            <div class="col-auto cursor-pointer text-primary copy_release_format_github ml-1" data-id="{{$release->id}}">
                <i class="fab fa-github"></i>
            </div>
        @endif
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
/** @var \App\Models\ReleaseChangelogCategory $category */
?>
@foreach ($release->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $groupedChange)
    <p>
        <?php /** @var $change \App\Models\ReleaseChangelogChange */?>
        {{ \App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->category }}:
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