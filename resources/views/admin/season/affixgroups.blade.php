<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Season;

/**
 * @var Season $season
 */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_season_affixgroup_table').DataTable({
                'aaSorting': [],
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection

<div class="row">
    <div class="col">
        <h4>{{ __('view_admin.season.edit.affix_groups.title') }}</h4>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.affixgroup.new', ['season' => $season]) }}"
           class="btn btn-success text-white float-end" role="button">
            <i class="fas fa-plus"></i> {{ __('view_admin.season.edit.affix_groups.add_affix_group') }}
        </a>
    </div>
</div>

<table id="admin_season_affixgroup_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.season.edit.affix_groups.table_header.id') }}</th>
        <th width="10%">{{ __('view_admin.season.edit.affix_groups.table_header.confirmed') }}</th>
        <th width="10%">{{ __('view_admin.season.edit.affix_groups.table_header.seasonal_index') }}</th>
        <th width="45%">{{ __('view_admin.season.edit.affix_groups.table_header.affixes') }}</th>
        <th width="25%">{{ __('view_admin.season.edit.affix_groups.table_header.actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($season->affixGroups as $affixGroup)
        <tr>
            <td>{{ $affixGroup->id }}</td>
            <td>
                <i class="fas {{ $affixGroup->confirmed ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
            </td>
            <td>{{ $affixGroup->seasonal_index }}</td>
            <td>{{ $affixGroup->text }}</td>
            <td>
                <div class="d-inline-flex gap-1">
                    <a class="btn btn-primary"
                       href="{{ route('admin.affixgroup.edit', ['season' => $season, 'affixGroup' => $affixGroup]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.season.edit.affix_groups.edit') }}
                    </a>
                    <form method="POST"
                          action="{{ route('admin.affixgroup.delete', ['season' => $season, 'affixGroup' => $affixGroup]) }}"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i>&nbsp;{{ __('view_admin.season.edit.affix_groups.delete') }}
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
