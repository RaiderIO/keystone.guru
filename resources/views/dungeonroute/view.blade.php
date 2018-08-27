@extends('layouts.app', ['wide' => true])
@section('header-title', $model->title)


@section('content')
    @isset($model)
        <div class="col-lg-12">
            <div id="map_container">
                @include('common.maps.map', [
                    'dungeon' => \App\Models\Dungeon::findOrFail($model->dungeon_id),
                    'model' => $model,
                    'edit' => false
                ])
            </div>
        </div>
    @endisset
@endsection

