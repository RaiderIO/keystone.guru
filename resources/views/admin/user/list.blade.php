
<?php

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use Illuminate\Support\Collection;

/**
 * @var Collection<PatreonBenefit> $allPatreonBenefits
 * @var Collection<Role>           $allRoles
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.user.list.title')])

@section('header-title')
    {{ __('view_admin.user.list.header') }}
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        /** @type object */
        let patreonBenefits = {!! $allPatreonBenefits; !!};
        /** @type object */
        let roles = {!! $allRoles; !!};

        $(function () {
            // On user_ip_addresses_modal modal show, fill it with the ip addresses
            $('#user_ip_addresses_modal').on('show.bs.modal', function (event) {
                let button = $(event.relatedTarget);
                let ipAddresses = button.data('ip-addresses').replaceAll(',', "\n");

                $('#user_ip_addresses_textarea').val(ipAddresses);
            });

            $('#admin_user_table').DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'ajax': {
                    'url': '/ajax/admin/user'
                },
                'drawCallback': function (settings) {
                    refreshSelectPickers();

                    // Add a new row when the button is pressed
                    $('select.patreon_benefits').bind('change', function () {
                        let $this = $(this);

                        $.ajax({
                            type: 'PUT',
                            url: `/ajax/user/${$this.data('userid')}/patreon/benefit`,
                            data: {
                                patreonBenefits: $this.val()
                            },
                            dataType: 'json',
                            success: function () {
                                showSuccessNotification(lang.get('messages.updated_patreon_benefits_successfully_label'));
                            }
                        });
                    });
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                // Order by affixes by default
                'order': [[0, 'asc']],
                'columns': [
                    {
                        'title': lang.get('messages.id_label'),
                        'data': 'id',
                        'name': 'id'
                    },
                    {
                        'title': lang.get('messages.name_label'),
                        'data': 'name',
                        'name': 'name'
                    },
                    {
                        'title': lang.get('messages.email_label'),
                        'data': 'email',
                        'name': 'email'
                    },
                    {
                        'title': lang.get('messages.route_count_label'),
                        'data': 'routes',
                        'name': 'routes',
                        'searchable': false
                    },
                    {
                        'title': lang.get('messages.roles_label'),
                        'data': 'roles_string',
                        'name': 'roles_string',
                        'searchable': false
                    },
                    {
                        'title': lang.get('messages.registered_label'),
                        'data': 'created_at',
                        'name': 'created_at',
                        'searchable': false,
                        'render': function (data, type, row, meta) {
                            let createdAtDate = (new Date(row.created_at));
                            return createdAtDate.getFullYear() +
                                '/' + _.padStart(createdAtDate.getMonth() + 1, 2, '0') +
                                '/' + _.padStart(createdAtDate.getDate(), 2, '0') +
                                ' ' + _.padStart(createdAtDate.getHours(), 2, '0') +
                                ':' + _.padStart(createdAtDate.getMinutes(), 2, '0') +
                                ':' + _.padStart(createdAtDate.getSeconds(), 2, '0');
                        }
                    },
                    {
                        'title': lang.get('messages.actions_label'),
                        'data': 'id',
                        'name': 'id',
                        'orderable': false,
                        'searchable': false,
                        'render': function (data, type, row, meta) {
                            let template = Handlebars.templates['admin_users_table_row_actions'];

                            return template($.extend({}, getHandlebarsDefaultVariables(), {
                                user: row,
                                roles: roles
                            }));
                        }
                    },
                    {
                        'title': lang.get('messages.patreon_label'),
                        'data': 'id',
                        'name': 'id',
                        'orderable': false,
                        'searchable': false,
                        'render': function (data, type, row, meta) {
                            let result = '';
                            if (row.patreon_user_link !== null) {
                                let template = Handlebars.templates['admin_users_table_row_patreon'];

                                let patreonBenefitsCopy = JSON.parse(JSON.stringify(patreonBenefits));
                                for (let i = 0; i < row.patreon_user_link.patreonbenefits.length; i++) {
                                    let userPaidTier = row.patreon_user_link.patreonbenefits[i];
                                    for (let j = 0; j < patreonBenefitsCopy.length; j++) {
                                        // Translate the patreon benefit's name
                                        patreonBenefitsCopy[j].name = lang.get(patreonBenefitsCopy[j].name);

                                        if (patreonBenefitsCopy[j].id === userPaidTier.id) {
                                            patreonBenefitsCopy[j].selected = true;
                                        }
                                    }
                                }

                                result = template($.extend({}, getHandlebarsDefaultVariables(), row, {paidtiers: patreonBenefitsCopy}));
                            }

                            return result;
                        }
                    },
                ],
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {
                    'emptyTable': lang.get('messages.datatable_no_users_in_table')
                })
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_user_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="5%">{{ __('view_admin.user.list.table_header_id') }}</th>
            <th width="15%">{{ __('view_admin.user.list.table_header_name') }}</th>
            <th width="15%">{{ __('view_admin.user.list.table_header_email') }}</th>
            <th width="10%">{{ __('view_admin.user.list.table_header_routes') }}</th>
            <th width="10%">{{ __('view_admin.user.list.table_header_roles') }}</th>
            <th width="15%">{{ __('view_admin.user.list.table_header_registered') }}</th>
            <th width="10%">{{ __('view_admin.user.list.table_header_actions') }}</th>
            <th width="10%">{{ __('view_admin.user.list.table_header_patreons') }}</th>
        </tr>
        </thead>
    </table>

    @component('common.general.modal', ['id' => 'user_ip_addresses_modal'])
        <h4>{{ __('view_admin.user.list.ip_addresses_header') }}</h4>
        <p>
            <a href="{{ sprintf('https://dash.cloudflare.com/%s/keystone.guru/security/waf/tools', config('keystoneguru.cloudflare.id')) }}">
                {{ __('view_admin.user.list.ip_addresses_cloudflare_link') }}
            </a>
        </p>
        <textarea id="user_ip_addresses_textarea" class="w-100" rows="20"></textarea>
    @endcomponent

@endsection
