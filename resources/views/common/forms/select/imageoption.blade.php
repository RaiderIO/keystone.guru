<?php
/**
 * @var string $name
 * @var string $url
 */
$height ??= 32;
$width  ??= 32;
?>
<div class="row g-0">
    <div class="col-auto">
        <img style="width: {{$width}}px; height: {{$height}}px" src="{{$url}}" alt="img"/>
    </div>
    <div class="col ps-2">
        {{$name}}
    </div>
</div>
