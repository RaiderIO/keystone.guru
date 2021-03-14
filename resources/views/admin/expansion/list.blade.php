@extends('layouts.sitepage', ['showAds' => false, 'title' => __('Expansion listing')])

@section('header-title')
    <div class="row">
        <div class="col-lg">
            <h4>{{ __('View expansions') }}</h4>
        </div>
        <div class="ml-auto">
            <a href="{{ route('admin.expansion.new') }}" class="btn btn-success text-white pull-right ml-auto" role="button">
                <i class="fas fa-plus"></i> {{ __('Create expansion') }}
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_expansion_table').DataTable({
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_expansion_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('Icon') }}</th>
            <th width="10%">{{ __('Id') }}</th>
            <th width="50%">{{ __('Name') }}</th>
            <th width="20%">{{ __('Color') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($expansions->all() as $expansion)
            <tr>
                <td><img src="{{ $expansion->iconfile->getURL() }}" style="width: 32px; height: 32px;"/></td>
                <td>{{ $expansion->id }}</td>
                <td>{{ $expansion->name }}</td>
                <td>{{ $expansion->color }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.expansion.edit', ['expansion' => $expansion->id]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection()