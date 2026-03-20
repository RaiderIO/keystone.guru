<?php
$breadcrumbsParams ??= [];
$classes ??= '';
?>
@if(Diglactic\Breadcrumbs\Breadcrumbs::exists($breadcrumbs))
    <div class="row {{$classes}}">
        <div class="col mt-4">
            {{ Diglactic\Breadcrumbs\Breadcrumbs::render($breadcrumbs, ...$breadcrumbsParams) }}
        </div>
    </div>
@endif
