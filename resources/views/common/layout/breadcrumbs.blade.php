<?php
$breadcrumbsParams = isset($breadcrumbsParams) ? $breadcrumbsParams : [];
?>
@if(Diglactic\Breadcrumbs\Breadcrumbs::exists($breadcrumbs))
    <div class="row">
        <div class="col mt-4">
            {{ Diglactic\Breadcrumbs\Breadcrumbs::render($breadcrumbs, ...$breadcrumbsParams) }}
        </div>
    </div>
@endif