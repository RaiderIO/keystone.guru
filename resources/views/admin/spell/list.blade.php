@extends('layouts.app', ['showAds' => false, 'title' => __('Spell listing')])

@section('header-title')
    {{ __('View spells') }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.spell.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Create Spell') }}
    </a>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_spell_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <table id="admin_spell_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('Icon') }}</th>
            <th width="10%">{{ __('Id') }}</th>
            <th width="70%">{{ __('Name') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $spell)
            <tr>
                <td><img src="/images/spells/{{ $spell->icon_name }}.png"/></td>
                <td>{{ $spell->id }}</td>
                <td>{{ $spell->name }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.spell.edit', ['spell' => $spell->id]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection