@extends('layouts.sitepage', ['showAds' => false, 'title' => __('Release listing')])

@section('header-title')
    <div class="row">
        <div class="col-lg">
            <h4>{{ __('View releases') }}</h4>
        </div>
        <div class="ml-auto">
            <a href="{{ route('admin.release.new') }}" class="btn btn-success text-white pull-right ml-auto"
               role="button">
                <i class="fas fa-plus"></i> {{ __('Create release') }}
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_release_table').DataTable({
                'order': [[0, 'desc']]
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_release_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('Id') }}</th>
            <th width="75%">{{ __('Version') }}</th>
            <th width="15%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $release)
            <tr>
                <td>{{ $release->id }}</td>
                <td>{{ $release->version }}</td>
                <td>
                    <a class="btn btn-primary"
                       href="{{ route('admin.release.edit', ['release' => $release->version]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
                    </a>
{{--                    <a class="btn btn-primary"--}}
{{--                       href="{{ route('admin.release.', ['release' => $release->version]) }}">--}}
{{--                        <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}--}}
{{--                    </a>--}}
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection()