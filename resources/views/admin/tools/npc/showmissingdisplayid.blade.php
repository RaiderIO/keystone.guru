<?php

use App\Models\Npc\Npc;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Npc> $npcs
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.npcs.showmissingdisplayid.title')])

@section('header-title', __('view_admin.tools.npcs.showmissingdisplayid.header'))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#npcs_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection


@section('content')
    <div class="mb-3">
        <table id="npcs_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="10%">{{ __('view_admin.tools.npcs.showmissingdisplayid.table_header.id') }}</th>
                <th width="70%">{{ __('view_admin.tools.npcs.showmissingdisplayid.table_header.name') }}</th>
                <th width="20%">{{ __('view_admin.tools.npcs.showmissingdisplayid.table_header.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($npcs as $npc)
                <tr>
                    <td>{{ $npc->id }}</td>
                    <td>{{ __($npc->name) }}</td>
                    <td>
                        <a href="https://www.wowhead.com/npc={{ $npc->id }}" target="_blank">
                            {{ __('view_admin.tools.npcs.showmissingdisplayid.wowhead') }} <i
                                class="fa fa-external-link"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
