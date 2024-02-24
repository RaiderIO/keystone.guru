<?php
/**
 * @var string $name
 * @var string $url
 */
$height ??= 32;
$width  ??= 32;
?>
<div class="row no-gutters">
    <div class="col-auto">
        <img style="width: {{$width}}px; height: {{$height}}px" src="{{$url}}" alt="img"/>
    </div>
    <div class="col pl-2">
        {{$name}}
    </div>
</div>
