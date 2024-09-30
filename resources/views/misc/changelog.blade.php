<?php

use App\Models\Release;
use App\Models\ReleaseChangelogCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @var LengthAwarePaginator|Release[]       $releases
 * @var Collection<ReleaseChangelogCategory> $categories
 * @var boolean                              $isUserAdmin
 */
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.changelog.title')])

@section('header-title', __('view_misc.changelog.header'))
<?php // Only add the releases when we're an admin, otherwise empty it ?>
@include('common.general.inline', ['path' => 'release/view', 'options' => array_merge(
    ['max_release' => $releases->first()->id],
     $isUserAdmin ? ['releases' => $releases->all()] : []
)])

@section('content')
    @foreach($releases as $release)
        @include('common.release.release', ['release' => $release])
    @endforeach

    <div class="row mt-2">
        <div class="col">

        </div>
        <div class="col-auto">
            {{ $releases->onEachSide(2)->links() }}
        </div>
    </div>
@endsection
