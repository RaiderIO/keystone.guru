<?php
/**
 * @var string $name
 * @var string $url
 */
$height ??= 32;
$width  ??= 32;
?>
{{-- align-items-center: without it the label column stretches to the image's height and the text sits at
     its top edge instead of next to the middle of the image --}}
<div class="row g-0 align-items-center">
    <div class="col-auto">
        <img style="width: {{$width}}px; height: {{$height}}px" src="{{$url}}" alt="img"/>
    </div>
    <div class="col ps-2">
        {{$name}}
    </div>
</div>
