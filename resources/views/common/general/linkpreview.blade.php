<?php
/** @var string $url */
/** @var string $title */
/** @var string $description */
/** @var string|null $image */
?>
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
@isset($image)
    <meta property="og:image" content="{{ $image }}">
@endif
