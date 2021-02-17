<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="create_route_tab" data-toggle="tab" href="#create" role="tab"
           aria-controls="create_route" aria-selected="true">
            {{ __('Create route') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="create_route_import_mdt_tab" data-toggle="tab" href="#import" role="tab"
           aria-controls="create_route_import_mdt" aria-selected="false">
            {{ __('Import from MDT') }}
        </a>
    </li>
</ul>
<div class="tab-content">
    <div id="create" class="tab-pane fade show active mt-3" role="tabpanel" aria-labelledby="create_route_tab">
        @include('common.forms.createroute')
    </div>
    <div id="import" class="tab-pane fade mt-3" role="tabpanel" aria-labelledby="create_route_import_mdt_tab">
        @include('common.forms.mdtimport')
    </div>
</div>
