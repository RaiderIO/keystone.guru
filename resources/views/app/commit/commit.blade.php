<?php
/**
 * @var  array<string, mixed> $commit
 * @var array<int, string> $lines
 **/
?>
@if(!empty($lines) > 0)
    {{ implode('\n', $lines) }}

@endisset
