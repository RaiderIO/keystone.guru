<?php
/** @var $releases Illuminate\Pagination\LengthAwarePaginator|\App\Models\Release[] */
/** @var $categories \Illuminate\Support\Collection<\App\Models\ReleaseChangelogCategory> */
/** @var $isUserAdmin boolean */
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
