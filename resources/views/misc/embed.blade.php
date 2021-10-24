@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.embed.title')])

@section('header-title', __('views/misc.embed.header'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-6">
            <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model, 'sidebar' => 1, 'pullsDefaultState' => '0']) }}"
                    style="width: 800px; height: 600px; border: none;"></iframe>
        </div>
    </div>
@endsection
